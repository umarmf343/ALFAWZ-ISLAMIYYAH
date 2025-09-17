<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Completed - AlFawz Qur'an Institute</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            background: white;
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .highlight-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            text-align: center;
        }
        .student-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .review-details {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><span class="emoji">📚</span> Memorization Review Completed</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Assalamu Alaikum {{ $teacher_name }},
            </div>
            
            <div class="highlight-box">
                <div class="student-name">{{ $student_name }}</div>
                <div>has successfully completed a memorization review!</div>
            </div>
            
            <div class="review-details">
                <h3 style="margin-top: 0; color: #495057;"><span class="emoji">📖</span> Review Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Surah:</span>
                    <span class="detail-value">{{ $surah_name }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Ayah Number:</span>
                    <span class="detail-value">{{ $ayah_number }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Quality Score:</span>
                    <span class="detail-value">{{ $quality }}/5 <span class="emoji">⭐</span></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Ease Factor:</span>
                    <span class="detail-value">{{ number_format($ease_factor, 2) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Completed At:</span>
                    <span class="detail-value">{{ $completed_at }}</span>
                </div>
            </div>
            
            <p style="color: #6c757d; line-height: 1.8;">
                <span class="emoji">🎉</span> Alhamdulillah! Your student is making excellent progress in their Qur'anic memorization journey. 
                You can view detailed analytics and provide feedback through your teacher dashboard.
            </p>
            
            <div style="text-align: center;">
                <a href="{{ $dashboard_url }}" class="cta-button">
                    <span class="emoji">📊</span> View Dashboard
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>
                <strong>AlFawz Qur'an Institute</strong><br>
                Empowering Qur'anic Education with Technology
            </p>
            <p style="font-size: 12px; margin-top: 15px;">
                This is an automated notification. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>