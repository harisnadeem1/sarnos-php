<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness & Health - The Global Sports Journal</title>
    <meta name="description" content="Expert fitness advice, nutrition tips, and health guidance for athletes and fitness enthusiasts.">
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
        
        .content-sections {
            display: grid;
            gap: 3rem;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .section-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .section-content {
            padding: 2rem;
        }
        
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .tip-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #2a5298;
        }
        
        .tip-card h4 {
            color: #1e3c72;
            margin-bottom: 0.5rem;
        }
        
        .workout-plan {
            background: #e8f4f8;
            padding: 2rem;
            border-radius: 8px;
            margin: 2rem 0;
        }
        
        .workout-plan h3 {
            color: #1e3c72;
            margin-bottom: 1rem;
        }
        
        .workout-days {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .day {
            background: white;
            padding: 1rem;
            border-radius: 5px;
            text-align: center;
        }
        
        .day h5 {
            color: #2a5298;
            margin-bottom: 0.5rem;
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
            
            .tips-grid, .workout-days {
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
                    <a href="sports-news.php">Sports News</a>
                    <a href="fitness-health.php" class="active">Fitness & Health</a>
                    <a href="privacy-policy.php">Privacy Policy</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <section class="page-header">
                <h1>💪 Fitness & Health</h1>
                <p>Evidence-based fitness advice, nutrition guidance, and wellness strategies for optimal athletic performance and healthy living.</p>
            </section>

            <div class="content-sections">
                <!-- Nutrition Section -->
                <section class="section">
                    <div class="section-header">
                        <h2>🥗 Sports Nutrition Guidelines</h2>
                        <p>Fuel your body for peak performance with science-backed nutrition strategies</p>
                    </div>
                    <div class="section-content">
                        <p>Proper nutrition is the foundation of athletic success. Whether you're a weekend warrior or a competitive athlete, understanding how to fuel your body can dramatically improve your performance and recovery.</p>
                        
                        <div class="tips-grid">
                            <div class="tip-card">
                                <h4>Pre-Workout Nutrition</h4>
                                <p>Consume a balanced meal 2-3 hours before exercise, focusing on complex carbohydrates and lean proteins. For workouts lasting over 60 minutes, include 30-60g of carbohydrates 30 minutes before starting.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>Post-Workout Recovery</h4>
                                <p>Within 30 minutes post-exercise, consume a 3:1 or 4:1 ratio of carbohydrates to protein to optimize glycogen replenishment and muscle protein synthesis. Chocolate milk is an excellent natural option.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>Hydration Strategy</h4>
                                <p>Drink 500-600ml of water 2-3 hours before exercise, 200-300ml every 15-20 minutes during exercise, and 150% of fluid losses post-exercise. Monitor urine color as a hydration indicator.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>Micronutrient Focus</h4>
                                <p>Ensure adequate iron, vitamin D, B-vitamins, and antioxidants through diverse whole foods. These nutrients support energy metabolism, bone health, and recovery processes.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Training Section -->
                <section class="section">
                    <div class="section-header">
                        <h2>🏋️‍♂️ Training Programs for Beginners</h2>
                        <p>Structured workout plans designed to build strength, endurance, and confidence</p>
                    </div>
                    <div class="section-content">
                        <p>Starting a fitness journey can be overwhelming. Our beginner-friendly programs focus on proper form, gradual progression, and sustainable habits that will serve you throughout your fitness journey.</p>
                        
                        <div class="workout-plan">
                            <h3>4-Week Beginner Strength Training Program</h3>
                            <p>This program introduces fundamental movement patterns and builds a solid foundation for future training. Each workout should take 45-60 minutes including warm-up and cool-down.</p>
                            
                            <div class="workout-days">
                                <div class="day">
                                    <h5>Monday - Upper Body</h5>
                                    <p>Push-ups, Seated Rows, Shoulder Press, Planks</p>
                                </div>
                                <div class="day">
                                    <h5>Tuesday - Lower Body</h5>
                                    <p>Squats, Lunges, Glute Bridges, Calf Raises</p>
                                </div>
                                <div class="day">
                                    <h5>Wednesday - Active Recovery</h5>
                                    <p>Light walking, Stretching, Yoga</p>
                                </div>
                                <div class="day">
                                    <h5>Thursday - Full Body</h5>
                                    <p>Deadlifts, Pull-ups, Dips, Mountain Climbers</p>
                                </div>
                                <div class="day">
                                    <h5>Friday - Cardio</h5>
                                    <p>20-30 min steady-state cardio</p>
                                </div>
                                <div class="day">
                                    <h5>Weekend - Rest</h5>
                                    <p>Complete rest or light recreational activities</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recovery Section -->
                <section class="section">
                    <div class="section-header">
                        <h2>🛌 Recovery & Regeneration</h2>
                        <p>Optimize your recovery to maximize training adaptations and prevent injury</p>
                    </div>
                    <div class="section-content">
                        <p>Recovery is where adaptation happens. Without proper recovery protocols, even the best training programs will yield suboptimal results and increase injury risk.</p>
                        
                        <div class="tips-grid">
                            <div class="tip-card">
                                <h4>Sleep Optimization</h4>
                                <p>Aim for 7-9 hours of quality sleep nightly. Maintain consistent sleep and wake times, create a cool, dark environment, and avoid screens 1 hour before bed.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>Active Recovery</h4>
                                <p>Include light movement on rest days: gentle yoga, walking, swimming, or mobility work. This promotes blood flow and reduces muscle stiffness without adding training stress.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>Stress Management</h4>
                                <p>Chronic stress impairs recovery. Practice stress-reduction techniques like meditation, deep breathing, or journaling. Monitor training load to avoid overreaching.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>Recovery Techniques</h4>
                                <p>Incorporate foam rolling, stretching, and self-massage. Consider cold therapy, saunas, or massage therapy for enhanced recovery, though consistency with basics matters most.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Mental Health Section -->
                <section class="section">
                    <div class="section-header">
                        <h2>🧠 Mental Health & Sports Psychology</h2>
                        <p>Strengthen your mind-body connection for enhanced performance and well-being</p>
                    </div>
                    <div class="section-content">
                        <p>Mental health and physical performance are intrinsically linked. Developing psychological skills enhances not only athletic performance but overall quality of life.</p>
                        
                        <h3>5 Ways to Improve Your Stamina in Just 30 Days</h3>
                        <div class="tips-grid">
                            <div class="tip-card">
                                <h4>1. Progressive Cardio Training</h4>
                                <p>Start with 20-minute sessions at moderate intensity. Increase duration by 5 minutes weekly while maintaining the same effort level. Mix steady-state and interval training.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>2. Breathing Techniques</h4>
                                <p>Practice diaphragmatic breathing daily. During exercise, focus on rhythmic breathing patterns. This improves oxygen efficiency and delays fatigue onset.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>3. Strength Training Integration</h4>
                                <p>Include 2-3 strength sessions weekly. Stronger muscles work more efficiently, reducing energy expenditure during endurance activities and improving overall stamina.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>4. Nutrition Timing</h4>
                                <p>Eat complex carbohydrates 2-3 hours before training. During long sessions, consume 30-60g carbs per hour. Proper fueling prevents energy depletion.</p>
                            </div>
                            
                            <div class="tip-card">
                                <h4>5. Recovery Prioritization</h4>
                                <p>Ensure adequate sleep and include rest days. Overtraining reduces stamina gains. Progressive overload works only with proper recovery between sessions.</p>
                            </div>
                        </div>
                    </div>
                </section>
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