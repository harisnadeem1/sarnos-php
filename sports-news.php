<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports News - The Global Sports Journal</title>
    <meta name="description" content="Latest sports news, athlete profiles, and performance analysis from around the globe.">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        .nav {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav a:hover, .nav a.active {
            color: #ffd700;
        }
        
        /* Main Content */
        .main {
            padding: 3rem 0;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: #1e3c72;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            font-size: 1.2rem;
            color: #666;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .article {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .article:hover {
            transform: translateY(-5px);
        }
        
        .article-content {
            padding: 2rem;
        }
        
        .article h2 {
            color: #1e3c72;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .article-meta {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .article p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .read-more {
            color: #2a5298;
            text-decoration: none;
            font-weight: 500;
        }
        
        .read-more:hover {
            text-decoration: underline;
        }
        
        /* Footer */
        .footer {
            background: #1e3c72;
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-top: 3rem;
        }
        
        .footer p {
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .nav {
                gap: 1rem;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="alternative_page.php" class="logo">🏆 The Global Sports Journal</a>
                <nav class="nav">
                    <a href="alternative_page.php">Home</a>
                    <a href="sports-news.php" class="active">Sports News</a>
                    <a href="fitness-health.php">Fitness & Health</a>
                    <a href="privacy-policy.php">Privacy Policy</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <section class="page-header">
                <h1>📰 Sports News</h1>
                <p>Stay informed with the latest developments in the world of sports, from professional leagues to grassroots athletics.</p>
            </section>

            <div class="articles-grid">
                <article class="article">
                    <div class="article-content">
                        <h2>Top 10 Greatest Football Players of All Time</h2>
                        <div class="article-meta">Published on <?php echo date('F j, Y', strtotime('-2 days')); ?> | Sports Analysis</div>
                        <p>Football has produced countless legendary players who have left an indelible mark on the beautiful game. From Pelé's mesmerizing skills to Messi's precision and Ronaldo's athleticism, these athletes have transcended the sport itself.</p>
                        <p>In this comprehensive analysis, we examine the careers, achievements, and lasting impact of the ten players who have truly defined football excellence. We consider their technical abilities, consistency, influence on their teams, and contributions to the sport's evolution.</p>
                        <p>The criteria for this ranking include individual awards, team achievements, statistical performance, and the revolutionary impact each player had on how football is played and perceived globally.</p>
                        <a href="#" class="read-more">Continue reading...</a>
                    </div>
                </article>

                <article class="article">
                    <div class="article-content">
                        <h2>Why Mental Strength Matters More Than Physical Strength in MMA</h2>
                        <div class="article-meta">Published on <?php echo date('F j, Y', strtotime('-1 day')); ?> | Combat Sports</div>
                        <p>Mixed Martial Arts has evolved far beyond a test of pure physical prowess. Today's elite fighters understand that mental preparation and psychological resilience often determine victory in the octagon.</p>
                        <p>Sports psychologists and veteran fighters share insights into the mental training regimens that separate champions from contenders. Visualization techniques, stress management, and emotional control play crucial roles in performance.</p>
                        <p>We explore case studies from recent championship fights where mental fortitude proved decisive, analyzing the psychological strategies employed by successful fighters and their impact on fight outcomes.</p>
                        <a href="#" class="read-more">Continue reading...</a>
                    </div>
                </article>

                <article class="article">
                    <div class="article-content">
                        <h2>Women in Sports: Breaking Records and Boundaries in 2025</h2>
                        <div class="article-meta">Published on <?php echo date('F j, Y'); ?> | Sports Equality</div>
                        <p>This year has witnessed unprecedented achievements by female athletes across multiple disciplines. From tennis courts to swimming pools, women continue to shatter barriers and redefine what's possible in competitive sports.</p>
                        <p>The increased visibility and investment in women's sports has created new opportunities and inspired the next generation of female athletes. Prize money equality, media coverage, and sponsorship deals have reached new heights.</p>
                        <p>We highlight the breakthrough performances, record-breaking achievements, and influential female athletes who are changing the landscape of professional sports while inspiring millions worldwide.</p>
                        <a href="#" class="read-more">Continue reading...</a>
                    </div>
                </article>

                <article class="article">
                    <div class="article-content">
                        <h2>The Science Behind Athletic Performance Peaks</h2>
                        <div class="article-meta">Published on <?php echo date('F j, Y', strtotime('-3 days')); ?> | Sports Science</div>
                        <p>Understanding when athletes reach their performance peaks has become crucial for career planning and competitive strategy. Recent research reveals fascinating patterns across different sports and disciplines.</p>
                        <p>Sports scientists have identified various factors that influence peak performance timing, including training methodologies, recovery protocols, and genetic predispositions that vary significantly between individual athletes.</p>
                        <p>This analysis examines data from multiple sports to understand optimal performance windows and how modern training techniques are extending athletic careers while maintaining elite-level competition.</p>
                        <a href="#" class="read-more">Continue reading...</a>
                    </div>
                </article>

                <article class="article">
                    <div class="article-content">
                        <h2>Olympic Sports: Hidden Gems and Rising Disciplines</h2>
                        <div class="article-meta">Published on <?php echo date('F j, Y', strtotime('-5 days')); ?> | Olympic Sports</div>
                        <p>While mainstream Olympic sports capture global attention, numerous lesser-known disciplines showcase incredible athleticism and dedication. These hidden gems of Olympic competition deserve recognition for their unique challenges and skilled athletes.</p>
                        <p>From modern pentathlon to sport climbing, we explore the technical demands, training requirements, and growing popularity of Olympic sports that often fly under the radar but provide thrilling competition.</p>
                        <p>Learn about the athletes who dedicate their lives to perfecting these specialized skills and the passionate communities that support these developing sports around the world.</p>
                        <a href="#" class="read-more">Continue reading...</a>
                    </div>
                </article>

                <article class="article">
                    <div class="article-content">
                        <h2>Youth Sports Development: Building Champions of Tomorrow</h2>
                        <div class="article-meta">Published on <?php echo date('F j, Y', strtotime('-1 week')); ?> | Youth Athletics</div>
                        <p>The foundation of athletic excellence begins in youth sports programs worldwide. Proper development during formative years creates not just skilled athletes, but well-rounded individuals with valuable life skills.</p>
                        <p>Coaches, parents, and sports organizations are implementing new approaches to youth development that emphasize fun, skill acquisition, and long-term athletic development rather than early specialization.</p>
                        <p>We examine successful youth sports models from different countries and cultures, highlighting best practices that foster both athletic achievement and personal growth in young athletes.</p>
                        <a href="#" class="read-more">Continue reading...</a>
                    </div>
                </article>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> The Global Sports Journal. All rights reserved.</p>
            <p>Educational content for sports enthusiasts worldwide | Last updated: <?php echo date('F j, Y'); ?></p>
        </div>
    </footer>
</body>
</html> 