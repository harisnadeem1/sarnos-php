<?php
require_once 'webassembly_countermeasures.php';

class CloakingSystem {
    private $config;
    private $configFile;
    private $wasmCounterMeasures;
    
    public function __construct() {
        // Zet de tijdzone voor correcte timestamps
        date_default_timezone_set('Europe/Amsterdam');
        
        // Bepaal het juiste pad naar het config bestand
        $this->configFile = $this->getConfigPath();
        $this->loadConfig();
        
        // Initialize WebAssembly counter-measures
        $this->wasmCounterMeasures = new WebAssemblyCounterMeasures();
    }
    
    private function getConfigPath() {
        // Als we in de admin directory zijn, ga één level omhoog
        if (basename(getcwd()) === 'admin') {
            return '../cloaking_config.json';
        }
        return 'cloaking_config.json';
    }
    
    private function getMonitoringLogPath() {
        // Als we in de admin directory zijn, ga één level omhoog
        if (basename(getcwd()) === 'admin') {
            return '../live_monitoring.json';
        }
        return 'live_monitoring.json';
    }
    
    private function loadConfig() {
        if (file_exists($this->configFile)) {
            $this->config = json_decode(file_get_contents($this->configFile), true);
        } else {
            $this->config = [
                'enabled' => false,
                'allowed_countries' => [],
                'cloaking_redirect_url' => 'alternative_page.php',
                'ip_whitelist' => [],
                'hide_cloaking_url' => true,
                'block_cloud_providers' => false
            ];
        }
    }
    
    public function saveConfig($newConfig) {
        try {
            // Expliciet overschrijven van configuratie waarden om problemen met array_merge te voorkomen
            foreach ($newConfig as $key => $value) {
                $this->config[$key] = $value;
            }
            
            // Controleer of directory bestaat en beschrijfbaar is
            $configDir = dirname($this->configFile);
            if (!is_dir($configDir)) {
                throw new Exception("Config directory bestaat niet: " . $configDir);
            }
            
            if (!is_writable($configDir)) {
                throw new Exception("Config directory is niet schrijfbaar: " . $configDir);
            }
            
            // Controleer of bestand bestaat en schrijfbaar is
            if (file_exists($this->configFile) && !is_writable($this->configFile)) {
                throw new Exception("Config bestand is niet schrijfbaar: " . $this->configFile);
            }
            
            // Probeer JSON te encoderen
            $jsonData = json_encode($this->config, JSON_PRETTY_PRINT);
            if ($jsonData === false) {
                throw new Exception("JSON encoding gefaald: " . json_last_error_msg());
            }
            
            // Schrijf naar bestand
            $result = file_put_contents($this->configFile, $jsonData);
            
            if ($result === false) {
                $error = error_get_last();
                throw new Exception("Kon configuratie niet opslaan naar " . $this->configFile . 
                    ". Error: " . ($error ? $error['message'] : 'Onbekende fout'));
            }
            
            // Herlaad configuratie om te verifiëren
            $this->loadConfig();
            
            return $result;
            
        } catch (Exception $e) {
            // Log de fout voor debugging
            error_log("CloakingSystem saveConfig error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    public function getVisitorIP() {
        // Cloudflare
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        
        // Load balancer / proxy headers (check multiple variations)
        $proxyHeaders = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP', 
            'HTTP_CLIENT_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        ];
        
        foreach ($proxyHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                // Validate it's a real IP (not private/local unless we're testing)
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
                // For development/testing, also accept private ranges
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        // Standard REMOTE_ADDR
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return trim($_SERVER['REMOTE_ADDR']);
        }
        
        // Default fallback
        return '127.0.0.1';
    }
    
    public function getCountryFromIP($ip) {
        // Check voor manual override (voor testing/debugging)
        if (isset($this->config['manual_country_override']) && !empty($this->config['manual_country_override'])) {
            return $this->config['manual_country_override'];
        }
        
        // Check voor lokale IP adressen
        $localIPs = ['127.0.0.1', '::1', 'localhost', '192.168.', '10.', '172.'];
        foreach ($localIPs as $localIP) {
            if (strpos($ip, $localIP) === 0) {
                // Voor test doeleinden, behandel localhost als Nederlands IP
                // Dit maakt het mogelijk om cloaking te testen op localhost
                return 'NL';
            }
        }
        
        // Special handling voor IPv6 localhost
        if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
            return 'NL';
        }
        
        // VERBETERDE MULTI-SOURCE VERIFICATIE
        $detectedCountries = [];
        
        // Bron 1: ip-api.com (primair)
        $country1 = $this->tryGeolocationAPI($ip);
        if ($country1 !== 'UNKNOWN') {
            $detectedCountries[] = $country1;
        }
        
        // Bron 2: ipinfo.io (backup)
        $country2 = $this->tryIPInfoAPI($ip);
        if ($country2 !== 'UNKNOWN') {
            $detectedCountries[] = $country2;
        }
        
        // Bron 3: ipstack.com (laatste fallback)
        if (empty($detectedCountries)) {
            $country3 = $this->tryIPStackAPI($ip);
            if ($country3 !== 'UNKNOWN') {
                $detectedCountries[] = $country3;
            }
        }
        
        // CONSENSUS LOGICA
        if (empty($detectedCountries)) {
            $this->logGeolocationFailure($ip);
            return 'UNKNOWN';
        }
        
        // Als meerdere bronnen hetzelfde zeggen = betrouwbaar
        $countryCounts = array_count_values($detectedCountries);
        arsort($countryCounts);
        $mostCommonCountry = key($countryCounts);
        
        // Log potentiële discrepantie voor monitoring
        if (count($detectedCountries) > 1 && count(array_unique($detectedCountries)) > 1) {
            $this->logGeolocationDiscrepancy($ip, $detectedCountries, $mostCommonCountry);
        }
        
        return $mostCommonCountry;
    }
    
    private function tryGeolocationAPI($ip) {
        try {
            $url = "http://ip-api.com/json/" . $ip . "?fields=countryCode,isp,org,as";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'Mozilla/5.0 (compatible; CloakingSystem/1.0)',
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response && strlen($response) > 0) {
                $data = json_decode($response, true);
                if ($data && isset($data['countryCode']) && !empty($data['countryCode'])) {
                    // Cache ISP data for this IP
                    $this->cacheISPData($ip, $data);
                    return $data['countryCode'];
                }
            }
        } catch (Exception $e) {
            // Silent fail, try next method
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Probeer ipinfo.io als tweede bron
     */
    private function tryIPInfoAPI($ip) {
        try {
            $url = "https://ipinfo.io/" . $ip . "/json";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'user_agent' => 'Mozilla/5.0 (compatible; CloakingSystem/1.0)',
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response && strlen($response) > 0) {
                $data = json_decode($response, true);
                if ($data && isset($data['country']) && !empty($data['country'])) {
                    return $data['country'];
                }
            }
        } catch (Exception $e) {
            // Silent fail
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Probeer ipstack.com als derde bron (gratis tier)
     */
    private function tryIPStackAPI($ip) {
        try {
            // Gratis API key niet nodig voor basis info
            $url = "http://api.ipstack.com/" . $ip . "?fields=country_code";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'user_agent' => 'Mozilla/5.0 (compatible; CloakingSystem/1.0)',
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response && strlen($response) > 0) {
                $data = json_decode($response, true);
                if ($data && isset($data['country_code']) && !empty($data['country_code'])) {
                    return $data['country_code'];
                }
            }
        } catch (Exception $e) {
            // Silent fail
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Log geolocation failures voor monitoring
     */
    private function logGeolocationFailure($ip) {
        $logFile = $this->getGeolocationLogFile();
        $logEntry = [
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'ip' => $ip,
            'type' => 'failure',
            'message' => 'Alle geolocation bronnen faalden voor IP: ' . $ip
        ];
        
        $this->appendToGeolocationLog($logFile, $logEntry);
    }
    
    /**
     * Log discrepanties tussen geolocation bronnen
     */
    private function logGeolocationDiscrepancy($ip, $detectedCountries, $chosenCountry) {
        $logFile = $this->getGeolocationLogFile();
        $logEntry = [
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'ip' => $ip,
            'type' => 'discrepancy',
            'detected_countries' => $detectedCountries,
            'chosen_country' => $chosenCountry,
            'message' => 'Verschillende bronnen gaven verschillende landen voor IP: ' . $ip
        ];
        
        $this->appendToGeolocationLog($logFile, $logEntry);
    }
    
    /**
     * Pad naar geolocation log bestand
     */
    private function getGeolocationLogFile() {
        if (basename(getcwd()) === 'admin') {
            return '../geolocation_accuracy.json';
        }
        return 'geolocation_accuracy.json';
    }
    
    /**
     * Voeg entry toe aan geolocation log
     */
    private function appendToGeolocationLog($logFile, $logEntry) {
        try {
            $logs = [];
            if (file_exists($logFile)) {
                $content = file_get_contents($logFile);
                if ($content) {
                    $logs = json_decode($content, true) ?? [];
                }
            }
            
            $logs[] = $logEntry;
            
            // Houd alleen laatste 500 entries
            if (count($logs) > 500) {
                $logs = array_slice($logs, -500);
            }
            
            file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT), LOCK_EX);
        } catch (Exception $e) {
            // Silent fail - log niet essentieel
        }
    }
    
    /**
     * Cache ISP data van een IP adres
     */
    private function cacheISPData($ip, $data) {
        $cacheFile = $this->getISPCacheFile();
        
        // Lees bestaande cache
        $cache = [];
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            if ($content) {
                $cache = json_decode($content, true) ?? [];
            }
        }
        
        // Voeg ISP data toe
        $cache[$ip] = [
            'isp' => $data['isp'] ?? 'Unknown ISP',
            'org' => $data['org'] ?? 'Unknown Organization',
            'as' => $data['as'] ?? 'Unknown AS',
            'cached_at' => time()
        ];
        
        // Houd cache beperkt tot laatste 1000 IP's
        if (count($cache) > 1000) {
            $cache = array_slice($cache, -1000, 1000, true);
        }
        
        // Schrijf terug naar cache
        file_put_contents($cacheFile, json_encode($cache), LOCK_EX);
    }
    
    /**
     * Haal ISP informatie op van een IP adres
     */
    public function getISPInfo($ip) {
        // Check cache eerst
        $cacheFile = $this->getISPCacheFile();
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            if ($content) {
                $cache = json_decode($content, true) ?? [];
                if (isset($cache[$ip])) {
                    $cachedData = $cache[$ip];
                    // Check of cache niet ouder is dan 24 uur
                    if ((time() - $cachedData['cached_at']) < 86400) {
                        return $this->formatISPName($cachedData['isp'], $cachedData['org']);
                    }
                }
            }
        }
        
        // Als niet in cache, probeer live op te halen
        try {
            $url = "http://ip-api.com/json/" . $ip . "?fields=isp,org,as";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'user_agent' => 'Mozilla/5.0 (compatible; CloakingSystem/1.0)',
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response && strlen($response) > 0) {
                $data = json_decode($response, true);
                if ($data && isset($data['isp'])) {
                    // Cache deze data
                    $this->cacheISPData($ip, $data);
                    return $this->formatISPName($data['isp'], $data['org'] ?? '');
                }
            }
        } catch (Exception $e) {
            // Silent fail
        }
        
        return 'Unknown ISP';
    }
    
    /**
     * Format ISP naam voor Nederlandse providers
     */
    private function formatISPName($isp, $org = '') {
        // Nederlandse providers herkennen en mooie namen geven
        $knownProviders = [
            'ziggo' => '📡 Ziggo',
            'kpn' => '📞 KPN',
            'xs4all' => '🌐 XS4ALL',
            'telfort' => '📱 Telfort',
            'tele2' => '📱 Tele2',
            'tmobile' => '📱 T-Mobile',
            't-mobile' => '📱 T-Mobile',
            'vodafone' => '📱 Vodafone',
            'online.nl' => '🌐 Online.nl',
            'transip' => '☁️ TransIP',
            'amazon' => '☁️ Amazon AWS',
            'google' => '☁️ Google Cloud',
            'microsoft' => '☁️ Microsoft Azure',
            'digitalocean' => '☁️ DigitalOcean',
            'hetzner' => '☁️ Hetzner',
            'ovh' => '☁️ OVH',
            'linode' => '☁️ Linode'
        ];
        
        $searchText = strtolower($isp . ' ' . $org);
        
        foreach ($knownProviders as $key => $name) {
            if (strpos($searchText, $key) !== false) {
                return $name;
            }
        }
        
        // Als geen bekende provider, gebruik originele ISP naam
        return '🌐 ' . $isp;
    }
    
    /**
     * Pad naar ISP cache bestand
     */
    private function getISPCacheFile() {
        if (basename(getcwd()) === 'admin') {
            return '../isp_cache.json';
        }
        return 'isp_cache.json';
    }
    
    private function tryGeolocationCURL($ip) {
        try {
            $url = "http://ip-api.com/json/" . $ip . "?fields=countryCode";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; CloakingSystem/1.0)');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($response && $httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['countryCode']) && !empty($data['countryCode'])) {
                    return $data['countryCode'];
                }
            }
        } catch (Exception $e) {
            // Silent fail
        }
        
        return 'UNKNOWN';
    }
    
    private function matchesIPPattern($ip, $pattern) {
        // Exact match
        if ($ip === $pattern) {
            return true;
        }
        
        // Wildcard match (bijv. 192.168.1.*)
        if (strpos($pattern, '*') !== false) {
            $regexPattern = str_replace('*', '.*', preg_quote($pattern, '/'));
            return preg_match('/^' . $regexPattern . '$/', $ip);
        }
        
        // CIDR match (bijv. 192.168.1.0/24)
        if (strpos($pattern, '/') !== false) {
            return $this->matchesCIDR($ip, $pattern);
        }
        
        return false;
    }
    
    private function matchesCIDR($ip, $cidr) {
        list($network, $mask) = explode('/', $cidr);
        
        $ip_long = ip2long($ip);
        $network_long = ip2long($network);
        $mask_long = -1 << (32 - $mask);
        
        return ($ip_long & $mask_long) === ($network_long & $mask_long);
    }
    
    /**
     * Check of een IP adres van een bekende cloud provider komt
     * Dit helpt bij het blokkeren van bots en scrapers
     */
    public function isCloudProvider($ip) {
        if (!isset($this->config['block_cloud_providers']) || !$this->config['block_cloud_providers']) {
            return false;
        }
        
        $cloudRanges = $this->getCloudProviderRanges();
        
        foreach ($cloudRanges as $provider => $ranges) {
            foreach ($ranges as $range) {
                if ($this->matchesIPPattern($ip, $range)) {
                    return $provider;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Bekende IP ranges van cloud providers
     * Deze ranges worden vaak gebruikt door bots, scrapers en TikTok verificatie
     */
    private function getCloudProviderRanges() {
        return [
            'AWS' => [
                '3.0.0.0/8',
                '13.32.0.0/15',
                '13.35.0.0/16',
                '15.177.0.0/18',
                '18.144.0.0/15',
                '18.188.0.0/16',
                '18.208.0.0/13',
                '34.192.0.0/12',
                '34.208.0.0/12',
                '34.224.0.0/12',
                '35.72.0.0/13',
                '44.192.0.0/11',
                '52.0.0.0/11',
                '52.32.0.0/11',
                '52.64.0.0/12',
                '52.84.0.0/15',
                '52.94.0.0/16',
                '54.64.0.0/11',
                '54.144.0.0/12',
                '54.160.0.0/11',
                '54.192.0.0/12',
                '99.77.128.0/18',
                '107.20.0.0/14',
                '172.96.97.0/24',
                '174.129.0.0/16'
            ],
            'Google Cloud' => [
                '8.34.208.0/20',
                '8.35.192.0/21',
                '23.236.48.0/20',
                '23.251.128.0/19',
                '34.64.0.0/11',
                '34.96.0.0/12',
                '35.185.0.0/16',
                '35.186.0.0/16',
                '35.187.0.0/16',
                '35.188.0.0/15',
                '35.190.0.0/17',
                '35.192.0.0/14',
                '35.196.0.0/15',
                '35.198.0.0/16',
                '35.199.0.0/16',
                '35.200.0.0/13',
                '35.208.0.0/12',
                '35.224.0.0/12',
                '35.240.0.0/13',
                '104.154.0.0/15',
                '104.196.0.0/14',
                '107.167.160.0/19',
                '107.178.192.0/18',
                '130.211.0.0/16',
                '146.148.0.0/17',
                '162.216.148.0/22',
                '162.222.176.0/21',
                '173.255.112.0/20',
                '199.36.154.0/23',
                '199.36.156.0/24'
            ],
            'Microsoft Azure' => [
                '13.64.0.0/11',
                '13.104.0.0/14',
                '20.0.0.0/8',
                '23.96.0.0/13',
                '40.64.0.0/10',
                '51.0.0.0/8',
                '52.96.0.0/12',
                '52.112.0.0/14',
                '52.120.0.0/14',
                '52.125.0.0/16',
                '52.130.0.0/15',
                '52.132.0.0/14',
                '52.136.0.0/13',
                '52.145.0.0/16',
                '52.146.0.0/15',
                '52.148.0.0/14',
                '52.152.0.0/13',
                '52.160.0.0/11',
                '52.224.0.0/11',
                '65.52.0.0/14',
                '70.37.0.0/17',
                '70.37.128.0/18',
                '104.40.0.0/13',
                '104.208.0.0/13',
                '137.116.0.0/14',
                '138.91.0.0/16',
                '157.55.0.0/16',
                '168.61.0.0/16',
                '191.232.0.0/13'
            ],
            'DigitalOcean' => [
                '67.207.64.0/18',
                '68.183.0.0/16',
                '104.131.0.0/16',
                '134.209.0.0/16',
                '138.197.0.0/16',
                '138.68.0.0/16',
                '139.59.0.0/16',
                '142.93.0.0/16',
                '143.110.0.0/16',
                '147.182.0.0/16',
                '157.230.0.0/16',
                '159.65.0.0/16',
                '159.89.0.0/16',
                '161.35.0.0/16',
                '162.243.0.0/16',
                '164.90.0.0/16',
                '165.22.0.0/16',
                '165.227.0.0/16',
                '167.71.0.0/16',
                '167.99.0.0/16',
                '178.62.0.0/16',
                '188.166.0.0/16',
                '188.226.0.0/16',
                '192.241.0.0/16',
                '198.199.64.0/18',
                '206.189.0.0/16',
                '207.154.0.0/16',
                '209.97.128.0/18'
            ],
            'Linode' => [
                '45.33.0.0/16',
                '45.56.0.0/16',
                '45.79.0.0/16',
                '66.175.208.0/20',
                '69.164.192.0/18',
                '72.14.176.0/20',
                '74.207.224.0/19',
                '85.90.240.0/20',
                '96.126.96.0/19',
                '97.107.128.0/19',
                '103.3.60.0/22',
                '106.187.32.0/19',
                '109.74.192.0/20',
                '139.162.0.0/16',
                '151.236.216.0/21',
                '172.104.0.0/15',
                '173.255.192.0/18',
                '176.58.96.0/19',
                '178.79.128.0/18',
                '185.3.92.0/22',
                '192.46.208.0/20',
                '192.155.80.0/20',
                '198.58.96.0/19',
                '212.71.232.0/21',
                '213.219.32.0/19'
            ],
            'Hetzner' => [
                '5.9.0.0/16',
                '46.4.0.0/16',
                '78.46.0.0/15',
                '78.47.0.0/16',
                '88.99.0.0/16',
                '94.130.0.0/15',
                '116.203.0.0/16',
                '135.181.0.0/16',
                '136.243.0.0/16',
                '138.201.0.0/16',
                '142.132.0.0/16',
                '144.76.0.0/16',
                '148.251.0.0/16',
                '157.90.0.0/16',
                '159.69.0.0/16',
                '162.55.0.0/16',
                '168.119.0.0/16',
                '176.9.0.0/16',
                '178.63.0.0/16',
                '195.201.0.0/16',
                '213.133.96.0/19'
            ],
            'OVH' => [
                '5.39.0.0/16',
                '5.135.0.0/16',
                '37.59.0.0/16',
                '37.187.0.0/16',
                '46.105.0.0/16',
                '51.15.0.0/16',
                '51.75.0.0/16',
                '51.77.0.0/16',
                '51.79.0.0/16',
                '51.81.0.0/16',
                '51.83.0.0/16',
                '51.89.0.0/16',
                '51.91.0.0/16',
                '54.36.0.0/16',
                '54.37.0.0/16',
                '54.38.0.0/16',
                '87.98.128.0/17',
                '91.121.0.0/16',
                '92.222.0.0/16',
                '94.23.0.0/16',
                '137.74.0.0/16',
                '141.94.0.0/16',
                '141.95.0.0/16',
                '144.217.0.0/16',
                '145.239.0.0/16',
                '147.135.0.0/16',
                '149.202.0.0/16',
                '151.80.0.0/16',
                '152.228.128.0/17',
                '164.132.0.0/16',
                '167.114.0.0/16',
                '176.31.0.0/16',
                '178.32.0.0/15',
                '188.165.0.0/16',
                '192.95.0.0/16',
                '193.70.0.0/15',
                '198.27.64.0/18',
                '198.50.128.0/17',
                '199.231.188.0/22',
                '213.186.32.0/19',
                '213.251.128.0/18'
            ],
            'Alibaba Cloud' => [
                '8.208.0.0/12',
                '47.52.0.0/16',
                '47.74.0.0/15',
                '47.88.0.0/13',
                '47.96.0.0/11',
                '47.128.0.0/12',
                '59.110.0.0/16',
                '101.132.0.0/14',
                '106.14.0.0/15',
                '112.124.0.0/14',
                '114.55.0.0/16',
                '116.62.0.0/16',
                '118.178.0.0/16',
                '118.190.0.0/16',
                '119.23.0.0/16',
                '119.28.0.0/16',
                '120.24.0.0/14',
                '120.26.0.0/16',
                '120.27.0.0/16',
                '121.40.0.0/14',
                '121.43.0.0/16',
                '139.196.0.0/16',
                '140.205.0.0/16',
                '161.117.0.0/16',
                '182.92.0.0/14'
            ],
            'Oracle Cloud' => [
                '129.146.0.0/16',
                '129.148.0.0/16',
                '129.150.0.0/16',
                '129.153.0.0/16',
                '129.159.0.0/16',
                '130.35.0.0/16',
                '132.145.0.0/16',
                '134.70.0.0/16',
                '138.1.0.0/16',
                '140.91.0.0/16',
                '144.24.0.0/16',
                '147.154.0.0/16',
                '150.136.0.0/16',
                '152.67.0.0/16',
                '152.70.0.0/16',
                '158.101.0.0/16',
                '158.247.0.0/16',
                '160.1.0.0/16',
                '192.29.0.0/16',
                '193.122.0.0/16'
            ],
            'Tencent Cloud' => [
                '43.128.0.0/12',
                '43.132.0.0/16',
                '43.133.0.0/16',
                '43.134.0.0/16',
                '43.135.0.0/16',
                '43.136.0.0/13',
                '43.142.0.0/16',
                '43.143.0.0/16',
                '43.144.0.0/13',
                '43.152.0.0/13',
                '43.163.0.0/16',
                '49.232.0.0/14',
                '49.233.0.0/16',
                '81.68.0.0/14',
                '81.69.0.0/16',
                '81.70.0.0/16',
                '81.71.0.0/16',
                '94.191.0.0/17',
                '101.32.0.0/14',
                '101.33.0.0/16',
                '106.52.0.0/14',
                '106.53.0.0/16',
                '106.54.0.0/16',
                '106.55.0.0/16',
                '118.24.0.0/15',
                '118.25.0.0/16',
                '118.26.0.0/16',
                '118.89.0.0/16',
                '119.28.0.0/16',
                '119.29.0.0/16',
                '134.175.0.0/16',
                '150.109.0.0/16',
                '152.136.0.0/16',
                '162.14.0.0/16',
                '170.106.0.0/16',
                '182.254.0.0/16',
                '211.159.0.0/16'
            ]
        ];
    }
    
    public function shouldShowAlternativePage() {
        $visitorIP = $this->getVisitorIP();
        $visitorCountry = $this->getCountryFromIP($visitorIP);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (!$this->config['enabled']) {
            // Log dat cloaking uitstaat
            $this->logVisitDetailed('allowed', $visitorIP, $visitorCountry, $userAgent);
            return false;
        }
        
        // NIEUWE PRIORITEIT 1: WebAssembly Fingerprinting Detection (2025 Advanced!)
        if ($this->detectWebAssemblyFingerprinting()) {
            $this->logTikTokDetection('WebAssembly Fingerprinting detected', $visitorIP, $userAgent);
            return true;
        }
        
        // NIEUWE PRIORITEIT 2: TikTok Bot Detection 
        if ($this->isTikTokBot()) {
            $this->logTikTokDetection('TikTok Bot Headers/User-Agent detected', $visitorIP, $userAgent);
            return true;
        }
        
        // NIEUWE PRIORITEIT 3: TikTok Behavioral Detection  
        if ($this->detectTikTokBotBehavior()) {
            $this->logTikTokDetection('TikTok Bot Behavior detected', $visitorIP, $userAgent);
            return true;
        }
        
        // Check voor test parameters (voor development)
        if (isset($_GET['test_foreign'])) {
            $testCountry = $_GET['test_foreign'];
            $shouldBlock = !in_array($testCountry, $this->config['allowed_countries']);
            
            if ($shouldBlock) {
                $this->logVisitDetailed('test_blocked_' . $testCountry, $visitorIP, $testCountry, $userAgent);
            } else {
                $this->logVisitDetailed('test_allowed_' . $testCountry, $visitorIP, $testCountry, $userAgent);
            }
            
            return $shouldBlock;
        }
        
        // Check IP whitelist eerst - deze heeft prioriteit
        if ($this->isIPWhitelisted($visitorIP)) {
            $this->logVisitDetailed('ip_whitelisted', $visitorIP, $visitorCountry, $userAgent);
            return false; // IP is whitelisted, altijd toegang
        }
        
        // Check cloud provider blocking - na whitelist check
        $cloudProvider = $this->isCloudProvider($visitorIP);
        if ($cloudProvider !== false) {
            $this->logVisitDetailed('cloud_provider_blocked', $visitorIP, $visitorCountry, $userAgent, $cloudProvider);
            return true; // Cloud provider geblokkeerd
        }
        
        // Check of het land toegestaan is
        if (!in_array($visitorCountry, $this->config['allowed_countries'])) {
            $this->logVisitDetailed('country_blocked', $visitorIP, $visitorCountry, $userAgent);
            return true;
        }
        
        // Land is toegestaan
        $this->logVisitDetailed('country_allowed', $visitorIP, $visitorCountry, $userAgent);
        return false;
    }
    
    public function isIPWhitelisted($ip) {
        if (!isset($this->config['ip_whitelist']) || !is_array($this->config['ip_whitelist'])) {
            return false;
        }
        
        // Check exacte match
        if (in_array($ip, $this->config['ip_whitelist'])) {
            return true;
        }
        
        // Check CIDR ranges en wildcards
        foreach ($this->config['ip_whitelist'] as $whitelistEntry) {
            if ($this->matchesIPPattern($ip, $whitelistEntry)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getCloakingRedirectUrl() {
        return $this->config['cloaking_redirect_url'] ?? 'alternative_page.php';
    }
    
    public function logVisit($type, $ip, $country, $userAgent) {
        $logFile = 'cloaking_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] Type: $type, IP: $ip, Country: $country, UA: $userAgent\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Enhanced logging voor Live Monitoring
        $this->logVisitDetailed($type, $ip, $country, $userAgent);
    }
    
    public function logVisitDetailed($action, $ip, $country, $userAgent, $extra_info = '') {
        $logFile = $this->getMonitoringLogPath();
        $timestamp = time();
        $datetime = date('Y-m-d H:i:s');
        
        // Bepaal status op basis van actie
        $status = 'unknown';
        $description = '';
        
        switch ($action) {
            case 'allowed':
            case 'country_allowed':
                $status = 'toegelaten';
                $description = 'IP toegelaten - land staat op toegestane lijst';
                break;
            case 'cloaked':
            case 'country_blocked':
                $status = 'cloaked';
                $description = 'IP doorgestuurd naar cloaking pagina - land geblokkeerd';
                break;
            case 'whitelisted':
            case 'ip_whitelisted':
                $status = 'toegelaten';
                $description = 'IP toegelaten - staat op whitelist';
                break;
            case 'blocked':
                $status = 'geblokkeerd';
                $description = 'IP volledig geblokkeerd';
                break;
            case 'cloud_provider_blocked':
                $status = 'cloaked';
                $description = 'Cloud provider geblokkeerd' . ($extra_info ? ' (' . $extra_info . ')' : '') . ' - mogelijk bot/scraper';
                break;
            default:
                if (strpos($action, 'test_') === 0) {
                    $status = 'test';
                    $description = 'Test modus - ' . str_replace('test_', '', $action);
                } else {
                    $description = $action;
                }
        }
        
        // Haal ISP informatie op (async om performance te behouden)
        $isp = $this->getISPInfo($ip ?: '127.0.0.1');
        
        $logEntry = [
            'timestamp' => $timestamp,
            'datetime' => $datetime,
            'ip' => $ip ?: 'unknown',
            'country' => $country ?: 'UNKNOWN',
            'isp' => $isp,
            'status' => $status,
            'action' => $action,
            'description' => $description,
            'extra_info' => $extra_info,
            'user_agent' => substr($userAgent ?: 'Unknown', 0, 200), // Limiteer UA lengte
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'session_id' => session_id() ?: 'no-session',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'localhost',
            'remote_port' => $_SERVER['REMOTE_PORT'] ?? '0'
        ];
        
        // Lees bestaande logs
        $logs = [];
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if ($content) {
                $logs = json_decode($content, true) ?? [];
            }
        }
        
        // Voeg nieuwe log toe
        $logs[] = $logEntry;
        
        // Houd alleen laatste 1000 entries (voor performance)
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        // Schrijf terug naar bestand
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT), LOCK_EX);
    }
    
    public function getLiveMonitoringData($limit = 100, $filters = []) {
        $logFile = $this->getMonitoringLogPath();
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $content = file_get_contents($logFile);
        if (!$content) {
            return [];
        }
        
        $logs = json_decode($content, true) ?? [];
        
        // Filter toepassen
        if (!empty($filters)) {
            $logs = $this->filterLogs($logs, $filters);
        }
        
        // Sorteer op tijd (nieuwste eerst)
        usort($logs, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Limiteer resultaten
        return array_slice($logs, 0, $limit);
    }
    
    private function filterLogs($logs, $filters) {
        return array_filter($logs, function($log) use ($filters) {
            // Filter op IP
            if (!empty($filters['ip']) && strpos($log['ip'], $filters['ip']) === false) {
                return false;
            }
            
            // Filter op land
            if (!empty($filters['country']) && stripos($log['country'], $filters['country']) === false) {
                return false;
            }
            
            // Filter op status
            if (!empty($filters['status']) && $log['status'] !== $filters['status']) {
                return false;
            }
            
            // Filter op tijdsperiode (laatste x uren)
            if (!empty($filters['hours'])) {
                $cutoff = time() - ($filters['hours'] * 3600);
                if ($log['timestamp'] < $cutoff) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    public function getMonitoringStats() {
        $logFile = $this->getMonitoringLogPath();
        
        if (!file_exists($logFile)) {
            return [
                'total' => 0,
                'last_24h' => 0,
                'cloaked' => 0,
                'toegelaten' => 0,
                'geblokkeerd' => 0,
                'countries' => [],
                'top_ips' => []
            ];
        }
        
        $content = file_get_contents($logFile);
        if (!$content) {
            return [];
        }
        
        $logs = json_decode($content, true) ?? [];
        $cutoff24h = time() - (24 * 3600);
        
        $stats = [
            'total' => count($logs),
            'last_24h' => 0,
            'cloaked' => 0,
            'toegelaten' => 0,
            'geblokkeerd' => 0,
            'countries' => [],
            'top_ips' => []
        ];
        
        $ipCounts = [];
        $countryCounts = [];
        
        foreach ($logs as $log) {
            // Laatste 24 uur tellen
            if ($log['timestamp'] >= $cutoff24h) {
                $stats['last_24h']++;
            }
            
            // Status tellen
            switch ($log['status']) {
                case 'cloaked':
                    $stats['cloaked']++;
                    break;
                case 'toegelaten':
                    $stats['toegelaten']++;
                    break;
                case 'geblokkeerd':
                    $stats['geblokkeerd']++;
                    break;
            }
            
            // Land tellen
            if (!empty($log['country'])) {
                $countryCounts[$log['country']] = ($countryCounts[$log['country']] ?? 0) + 1;
            }
            
            // IP tellen
            $ipCounts[$log['ip']] = ($ipCounts[$log['ip']] ?? 0) + 1;
        }
        
        // Top 10 landen
        arsort($countryCounts);
        $stats['countries'] = array_slice($countryCounts, 0, 10, true);
        
        // Top 10 IP's
        arsort($ipCounts);
        $stats['top_ips'] = array_slice($ipCounts, 0, 10, true);
        
        return $stats;
    }
    
    public function clearMonitoringData() {
        $logFile = $this->getMonitoringLogPath();
        
        try {
            // Schrijf een lege array naar het bestand
            file_put_contents($logFile, json_encode([]), LOCK_EX);
            return true;
        } catch (Exception $e) {
            error_log("CloakingSystem clearMonitoringData error: " . $e->getMessage());
            return false;
        }
    }
    
    public function shouldHideCloakingUrl() {
        return $this->config['hide_cloaking_url'] ?? true;
    }
    
    public function serveCloakingContent() {
        // Set juiste headers
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex, nofollow');
        
        $cloakingFile = $this->getCloakingRedirectUrl();
        
        // Check of het een lokaal bestand is
        if (strpos($cloakingFile, 'http') === 0) {
            // Externe URL - we kunnen deze niet includen, dus redirect
            header("Location: " . $cloakingFile);
            exit();
        }
        
        // Lokaal bestand - include de content
        if (file_exists($cloakingFile)) {
            // Start output buffering om de content te capturen
            ob_start();
            include $cloakingFile;
            $content = ob_get_clean();
            
            // Serveer de content
            echo $content;
            exit();
        } else {
            // Fallback - als bestand niet bestaat, toon een standaard cloaking pagina
            $this->showDefaultCloakingPage();
            exit();
        }
    }
    
    private function showDefaultCloakingPage() {
        ?>
        <!DOCTYPE html>
        <html lang="nl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Welkom</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .container {
                    background: white;
                    border-radius: 15px;
                    padding: 40px;
                    max-width: 600px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                    text-align: center;
                }
                h1 { color: #2c3e50; margin-bottom: 20px; }
                p { margin-bottom: 15px; font-size: 1.1em; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🌐 Welkom op onze informatieve website</h1>
                <p>Dit is een uitgebreide informatieve pagina met algemene inhoud over onze diensten.</p>
                <p>Voor meer informatie kunt u contact met ons opnemen.</p>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Detecteer WebAssembly fingerprinting (2025 Advanced Detection)
     */
    public function detectWebAssemblyFingerprinting() {
        return $this->wasmCounterMeasures->detectWebAssemblyFingerprinting();
    }

    /**
     * Detecteer TikTok crawlers en bots op basis van headers en User-Agent
     */
    public function isTikTokBot() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $headers = getallheaders() ?: [];
        
        // TikTok-specific User-Agent patterns
        $tiktokPatterns = [
            '/tiktok/i',
            '/bytedance/i',
            '/musical\.ly/i',
            '/TikTok.*Bot/i',
            '/ByteSpider/i',
            '/tt-spider/i'
        ];
        
        foreach ($tiktokPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        // Check voor TikTok-specific headers
        $tiktokHeaders = [
            'X-Argus',
            'X-Ladon', 
            'X-Gorgon',
            'X-Khronos',
            'X-Helios',
            'X-Medusa',
            'X-Tt-Logid',
            'X-Ss-Stub'
        ];
        
        foreach ($tiktokHeaders as $header) {
            if (isset($headers[$header]) || isset($_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))])) {
                return true;
            }
        }
        
        // Detecteer headless browser signatures vaak gebruikt door TikTok crawlers
        $headlessSignatures = [
            'headless',
            'phantom',
            'selenium',
            'puppeteer',
            'playwright',
            'chrome-lighthouse'
        ];
        
        foreach ($headlessSignatures as $signature) {
            if (stripos($userAgent, $signature) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Advanced behavioral analysis voor TikTok bot detection
     */
    public function detectTikTokBotBehavior() {
        // Check voor DevTools API requests (zoals in uw logs)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $suspiciousEndpoints = [
            '/.well-known/appspecific/',
            '/json/version',
            '/json/list',
            '/json/runtime/',
            '/DevTools'
        ];
        
        foreach ($suspiciousEndpoints as $endpoint) {
            if (strpos($requestUri, $endpoint) !== false) {
                return true;
            }
        }
        
        // Check voor te snelle opeenvolgende requests (uit uw logs)
        $sessionFile = $this->getSessionTrackingFile();
        $currentTime = time();
        $clientIP = $this->getVisitorIP();
        
        if (file_exists($sessionFile)) {
            $sessions = json_decode(file_get_contents($sessionFile), true) ?: [];
            
            if (isset($sessions[$clientIP])) {
                $lastRequest = $sessions[$clientIP]['last_request'];
                $requestCount = $sessions[$clientIP]['count'] ?? 0;
                
                // Te snel na vorige request (< 1 seconde zoals in uw logs)
                if (($currentTime - $lastRequest) < 1) {
                    $requestCount++;
                    if ($requestCount > 3) {
                        return true; // Bot-like behavior
                    }
                }
            }
        }
        
        // Update session tracking
        $this->updateSessionTracking($clientIP, $currentTime);
        
        return false;
    }
    
    /**
     * Session tracking voor behavioral analysis
     */
    private function getSessionTrackingFile() {
        if (basename(getcwd()) === 'admin') {
            return '../session_tracking.json';
        }
        return 'session_tracking.json';
    }
    
    private function updateSessionTracking($ip, $timestamp) {
        $sessionFile = $this->getSessionTrackingFile();
        $sessions = [];
        
        if (file_exists($sessionFile)) {
            $sessions = json_decode(file_get_contents($sessionFile), true) ?: [];
        }
        
        $sessions[$ip] = [
            'last_request' => $timestamp,
            'count' => isset($sessions[$ip]) ? ($sessions[$ip]['count'] ?? 0) + 1 : 1,
            'first_seen' => $sessions[$ip]['first_seen'] ?? $timestamp
        ];
        
        // Cleanup oude entries (ouder dan 1 uur)
        foreach ($sessions as $sessionIP => $data) {
            if (($timestamp - $data['first_seen']) > 3600) {
                unset($sessions[$sessionIP]);
            }
        }
        
        file_put_contents($sessionFile, json_encode($sessions));
    }
    
    /**
     * Gespecialiseerde logging voor TikTok bot detecties
     */
    private function logTikTokDetection($reason, $ip, $userAgent) {
        $logFile = $this->getTikTokLogFile();
        $timestamp = time();
        $datetime = date('Y-m-d H:i:s');
        
        $logEntry = [
            'timestamp' => $timestamp,
            'datetime' => $datetime,
            'ip' => $ip,
            'reason' => $reason,
            'user_agent' => $userAgent,
            'headers' => $this->getAllHeaders(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'severity' => 'HIGH'
        ];
        
        // TikTok-specific log bestand
        $existingLogs = [];
        if (file_exists($logFile)) {
            $existingLogs = json_decode(file_get_contents($logFile), true) ?: [];
        }
        
        $existingLogs[] = $logEntry;
        
        // Bewaar alleen laatste 1000 entries
        if (count($existingLogs) > 1000) {
            $existingLogs = array_slice($existingLogs, -1000);
        }
        
        file_put_contents($logFile, json_encode($existingLogs, JSON_PRETTY_PRINT));
        
        // Ook naar algemene monitoring
        $this->logVisitDetailed('tiktok_bot_detected', $ip, 'UNKNOWN', $userAgent, $reason);
    }
    
    /**
     * Haal alle HTTP headers op voor logging
     */
    private function getAllHeaders() {
        $headers = [];
        
        // Probeer getallheaders() eerst
        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        }
        
        // Fallback: doorloop $_SERVER array
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$headerName] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Pad naar TikTok-specific log bestand
     */
    private function getTikTokLogFile() {
        if (basename(getcwd()) === 'admin') {
            return '../tiktok_bot_detection.json';
        }
        return 'tiktok_bot_detection.json';
    }
}

// Global functie voor makkelijk gebruik - Logt ALLE bezoeken voor Live Monitoring
function checkCloaking() {
    $cloaking = new CloakingSystem();
    $config = $cloaking->getConfig();
    
    // Altijd IP info ophalen voor logging
    $ip = $cloaking->getVisitorIP();
    $country = $cloaking->getCountryFromIP($ip);
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Debug informatie (alleen in development/test mode)
    if (isset($_GET['debug_cloaking'])) {
        $shouldCloak = $cloaking->shouldShowAlternativePage();
        
        echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 10px; border-radius: 5px; font-family: monospace;'>";
        echo "<strong>🔍 Cloaking Debug Info:</strong><br>";
        echo "Enabled: " . ($config['enabled'] ? 'YES' : 'NO') . "<br>";
        echo "IP: " . htmlspecialchars($ip) . "<br>";
        echo "Country: " . htmlspecialchars($country) . "<br>";
        echo "Allowed Countries: " . implode(', ', $config['allowed_countries']) . "<br>";
        echo "Should Cloak: " . ($shouldCloak ? 'YES' : 'NO') . "<br>";
        echo "Test Foreign: " . (isset($_GET['test_foreign']) ? htmlspecialchars($_GET['test_foreign']) : 'NO') . "<br>";
        echo "</div>";
    }
    
    // Check cloaking - dit logt automatisch de visit via shouldShowAlternativePage()
    $shouldCloak = $cloaking->shouldShowAlternativePage();
    
    if ($shouldCloak) {
        // Logging wordt al gedaan door shouldShowAlternativePage(), geen dubbele logging nodig
        
        // Check of we de URL moeten verbergen
        if ($cloaking->shouldHideCloakingUrl()) {
            // Serveer cloaking content zonder URL te veranderen
            $cloaking->serveCloakingContent();
        } else {
            // Oude gedrag: redirect naar de geconfigureerde cloaking pagina
            $redirectUrl = $cloaking->getCloakingRedirectUrl();
            header("Location: " . $redirectUrl);
            exit();
        }
    }
    
    return false;
}

?> 