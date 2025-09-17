<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Progress Summary - AlFawz Qur'an Institute</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .header {
            background: rgba(255,255,255,0.15);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .date-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
            display: inline-block;
        }
        .content {
            background: white;
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .students-section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .student-card {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 0 8px 8px 0;
            transition: transform 0.2s;
        }
        .student-card:hover {
            transform: translateX(5px);
        }
        .student-name {
            font-weight: 600;
            font-size: 16px;
            color: #495057;
            margin-bottom: 10px;
        }
        .student-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            font-size: 14px;
        }
        .student-stat {
            text-align: center;
        }
        .student-stat-number {
            font-weight: bold;
            color: #667eea;
            font-size: 18px;
        }
        .student-stat-label {
            color: #6c757d;
            font-size: 12px;
        }
        .no-activity {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .emoji {
            font-size: 1.2em;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 20px 15px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .student-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><span class="emoji">📊</span> Daily Progress Summary</h1>
            <div class="date-badge">{{ $date }}</div>
        </div>
        
        <div class="content">
            <div class="greeting">
                Assalamu Alaikum {{ $teacher_name }},
            </div>
            
            <p style="color: #6c757d; line-height: 1.8;">
                Here's your daily summary of memorization activities across all your classes.
            </p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">{{ $total_reviews }}</div>
                    <div class="stat-label"><span class="emoji">📝</span> Total Reviews</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $active_students }}</div>
                    <div class="stat-label"><span class="emoji">👥</span> Active Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($avg_quality, 1) }}</div>
                    <div class="stat-label"><span class="emoji">⭐</span> Avg Quality</div>
                </div>
            </div>
            
            @if(count($student_activities) > 0)
                <div class="students-section">
                    <h3 class="section-title"><span class="emoji">🎯</span> Student Activities</h3>
                    
                    @foreach($student_activities as $activity)
                        <div class="student-card">
                            <div class="student-name">{{ $activity['name'] }}</div>
                            <div class="student-stats">
                                <div class="student-stat">
                                    <div class="student-stat-number">{{ $activity['reviews_count'] }}</div>
                                    <div class="student-stat-label">Reviews</div>
                                </div>
                                <div class="student-stat">
                                    <div class="student-stat-number">{{ number_format($activity['avg_quality'], 1) }}</div>
                                    <div class="student-stat-label">Avg Quality</div>
                                </div>
                                <div class="student-stat">
                                    <div class="student-stat-number">{{ $activity['streak_days'] }}</div>
                                    <div class="student-stat-label">Streak Days</div>
                                </div>
                                <div class="student-stat">
                                    <div class="student-stat-number">{{ $activity['total_hasanat'] }}</div>
                                    <div class="student-stat-label">Hasanat</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="no-activity">
                    <span class="emoji">😴</span><br>
                    No memorization activities recorded today.<br>
                    <small>Encourage your students to continue their Qur'anic journey!</small>
                </div>
            @endif
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="{{ $dashboard_url }}" class="cta-button">
                    <span class="emoji">📈</span> View Full Analytics
                </a>
            </div>
            
            <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin-top: 30px;">
                <h4 style="margin-top: 0; color: #1565c0;"><span class="emoji">💡</span> Teaching Tip of the Day</h4>
                <p style="margin-bottom: 0; color: #1976d2; font-style: italic;">
                    "Encourage students to review during the blessed hours of Fajr and Maghrib. 
                    The barakah in these times enhances memorization and retention."
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p>
                <strong>AlFawz Qur'an Institute</strong><br>
                Empowering Qur'anic Education with Technology
            </p>
            <p style="font-size: 12px; margin-top: 15px;">
                This is an automated daily summary. You can adjust your notification preferences in your dashboard.
            </p>
        </div>
    </div>
</body>
</html>