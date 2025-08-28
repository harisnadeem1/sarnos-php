<?php
/**
 * Human Behavior Simulation voor Anti-Detection
 * 
 * Dit bestand simuleert menselijk gedrag om bot-detectie te omzeilen
 * gebaseerd op de patronen die TikTok en andere platforms gebruiken.
 */

class HumanBehaviorSimulator {
    
    private $sessionFile;
    
    public function __construct() {
        $this->sessionFile = 'human_behavior_sessions.json';
    }
    
    /**
     * Simuleer menselijke timing delays
     */
    public function addHumanTiming() {
        // Menselijke reactietijden: 200ms - 3 seconden
        $delay = $this->getRandomHumanDelay();
        
        // Voeg JavaScript toe voor client-side delays
        echo "<script>
        // Simuleer human scroll behavior
        function simulateHumanScroll() {
            let scrolled = 0;
            const maxScroll = document.body.scrollHeight - window.innerHeight;
            
            function randomScroll() {
                if (scrolled < maxScroll) {
                    const scrollAmount = Math.random() * 100 + 50; // 50-150px
                    window.scrollBy(0, scrollAmount);
                    scrolled += scrollAmount;
                    
                    // Random delay tussen scrolls (100-800ms)
                    setTimeout(randomScroll, Math.random() * 700 + 100);
                }
            }
            
            // Start scroll na random delay
            setTimeout(randomScroll, Math.random() * 2000 + 500);
        }
        
        // Simuleer mouse bewegingen
        function simulateMouseMovement() {
            let mouseX = Math.random() * window.innerWidth;
            let mouseY = Math.random() * window.innerHeight;
            
            function moveToRandomPosition() {
                const targetX = Math.random() * window.innerWidth;
                const targetY = Math.random() * window.innerHeight;
                
                const steps = 20 + Math.random() * 30; // 20-50 steps
                const stepX = (targetX - mouseX) / steps;
                const stepY = (targetY - mouseY) / steps;
                
                let currentStep = 0;
                
                function animateMove() {
                    if (currentStep < steps) {
                        mouseX += stepX + (Math.random() - 0.5) * 10; // Add jitter
                        mouseY += stepY + (Math.random() - 0.5) * 10;
                        
                        // Trigger mousemove event
                        const event = new MouseEvent('mousemove', {
                            clientX: mouseX,
                            clientY: mouseY
                        });
                        document.dispatchEvent(event);
                        
                        currentStep++;
                        setTimeout(animateMove, 10 + Math.random() * 20);
                    } else {
                        // Schedule next movement
                        setTimeout(moveToRandomPosition, 1000 + Math.random() * 3000);
                    }
                }
                
                animateMove();
            }
            
            moveToRandomPosition();
        }
        
        // Start simulations
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(simulateHumanScroll, " . ($delay * 1000) . ");
            setTimeout(simulateMouseMovement, " . (($delay + 0.5) * 1000) . ");
        });
        </script>";
    }
    
    /**
     * Genereer realistic human delays
     */
    private function getRandomHumanDelay() {
        // Menselijke reactietijden volgen een log-normale distributie
        $min = 0.2; // 200ms minimum
        $max = 3.0; // 3 seconden maximum
        
        // Gebruik Box-Muller transform voor normale distributie
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        
        $normal = sqrt(-2 * log($u1)) * cos(2 * pi() * $u2);
        
        // Transform naar log-normale distributie
        $delay = exp($normal * 0.5 + 0.5);
        
        // Clamp tussen min en max
        return max($min, min($max, $delay));
    }
    
    /**
     * Simuleer realistische page dwell time
     */
    public function getRealisticDwellTime() {
        // Gemiddelde dwell time: 15-45 seconden voor e-commerce
        $base = 15;
        $variation = 30;
        
        // Exponential decay voor meer realisme
        $random = mt_rand() / mt_getrandmax();
        $dwellTime = $base + $variation * (-log(1 - $random));
        
        return min(120, $dwellTime); // Max 2 minuten
    }
    
    /**
     * Simuleer realistic click patterns
     */
    public function simulateClickBehavior() {
        echo "<script>
        // Track clicks voor menselijk patroon
        let clickCount = 0;
        let lastClickTime = 0;
        
        document.addEventListener('click', function(e) {
            const now = Date.now();
            const timeSinceLastClick = now - lastClickTime;
            
            // Menselijke click intervals: minimaal 100ms
            if (timeSinceLastClick < 100) {
                e.preventDefault();
                return false;
            }
            
            clickCount++;
            lastClickTime = now;
            
            // Voeg kleine random delay toe voor menselijkheid
            setTimeout(function() {
                // Stuur analytics data
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'human_click', {
                        'click_count': clickCount,
                        'time_since_last': timeSinceLastClick
                    });
                }
            }, Math.random() * 50 + 10);
        });
        </script>";
    }
    
    /**
     * Anti-fingerprinting: randomize browser capabilities
     */
    public function addAntiFingerprinting() {
        echo "<script>
        // Randomize screen properties slightly
        (function() {
            const originalScreen = window.screen;
            
            Object.defineProperties(window.screen, {
                width: {
                    get: function() {
                        return originalScreen.width + Math.floor(Math.random() * 3 - 1);
                    }
                },
                height: {
                    get: function() {
                        return originalScreen.height + Math.floor(Math.random() * 3 - 1);
                    }
                }
            });
            
            // Slightly randomize navigator.hardwareConcurrency
            if (navigator.hardwareConcurrency) {
                Object.defineProperty(navigator, 'hardwareConcurrency', {
                    get: function() {
                        const original = " . (isset($_SERVER['HTTP_USER_AGENT']) ? '8' : '4') . ";
                        return original + Math.floor(Math.random() * 3 - 1);
                    }
                });
            }
            
            // Randomize timezone offset slightly
            const originalGetTimezoneOffset = Date.prototype.getTimezoneOffset;
            Date.prototype.getTimezoneOffset = function() {
                return originalGetTimezoneOffset.call(this) + Math.floor(Math.random() * 3 - 1);
            };
        })();
        </script>";
    }
    
    /**
     * Create realistic session progression
     */
    public function trackSessionProgression($page) {
        $sessionData = $this->getSessionData();
        $sessionId = session_id() ?: 'default_session';
        
        if (!isset($sessionData[$sessionId])) {
            $sessionData[$sessionId] = [
                'start_time' => time(),
                'pages' => [],
                'total_interactions' => 0,
                'human_score' => 100
            ];
        }
        
        // Add current page
        $sessionData[$sessionId]['pages'][] = [
            'page' => $page,
            'timestamp' => time(),
            'dwell_time' => $this->getRealisticDwellTime()
        ];
        
        // Calculate human score based on behavior
        $sessionData[$sessionId]['human_score'] = $this->calculateHumanScore($sessionData[$sessionId]);
        
        $this->saveSessionData($sessionData);
        
        return $sessionData[$sessionId];
    }
    
    /**
     * Calculate human-likeness score
     */
    private function calculateHumanScore($session) {
        $score = 100;
        $pages = $session['pages'];
        
        if (count($pages) < 2) {
            return $score;
        }
        
        // Check voor te snelle page transitions
        for ($i = 1; $i < count($pages); $i++) {
            $timeDiff = $pages[$i]['timestamp'] - $pages[$i-1]['timestamp'];
            
            if ($timeDiff < 2) {
                $score -= 20; // Penalty voor te snelle navigatie
            } elseif ($timeDiff < 5) {
                $score -= 10; // Kleine penalty
            }
        }
        
        // Check voor repetitive patterns
        $pageNames = array_column($pages, 'page');
        $uniquePages = array_unique($pageNames);
        
        if (count($uniquePages) < count($pages) * 0.7) {
            $score -= 15; // Penalty voor te veel herhaaldelijke pagina's
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Get session data from file
     */
    private function getSessionData() {
        if (file_exists($this->sessionFile)) {
            return json_decode(file_get_contents($this->sessionFile), true) ?: [];
        }
        return [];
    }
    
    /**
     * Save session data to file
     */
    private function saveSessionData($data) {
        // Cleanup old sessions (older than 24 hours)
        $cutoff = time() - (24 * 60 * 60);
        foreach ($data as $sessionId => $sessionData) {
            if ($sessionData['start_time'] < $cutoff) {
                unset($data[$sessionId]);
            }
        }
        
        file_put_contents($this->sessionFile, json_encode($data));
    }
    
    /**
     * Get current session's human score
     */
    public function getHumanScore() {
        $sessionData = $this->getSessionData();
        $sessionId = session_id() ?: 'default_session';
        
        return $sessionData[$sessionId]['human_score'] ?? 100;
    }
    
    /**
     * Inject all human behavior simulations
     */
    public function injectAllBehaviors($currentPage = '') {
        if (!empty($currentPage)) {
            $this->trackSessionProgression($currentPage);
        }
        
        $this->addHumanTiming();
        $this->simulateClickBehavior();
        $this->addAntiFingerprinting();
        
        // Add meta tags voor extra authenticity
        echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
        echo "<meta name='robots' content='index, follow'>";
        
        // Add some realistic third-party scripts (commented out)
        echo "<!-- Google Analytics placeholder -->";
        echo "<!-- Facebook Pixel placeholder -->";
    }
}

?> 