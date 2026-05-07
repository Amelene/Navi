<?php
session_start();

// Check if application was submitted
if (!isset($_SESSION['application_submitted'])) {
    header('Location: login.php');
    exit();
}

// Get application ID
$application_id = $_SESSION['application_id'] ?? 'N/A';

// Clear the application data
unset($_SESSION['application_data']);
unset($_SESSION['application_submitted']);
unset($_SESSION['application_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - Navi Shipping</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #126E82 0%, #0e5a6b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 16px;
            padding: 50px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: #16a34a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon svg {
            width: 60px;
            height: 60px;
            stroke: white;
            stroke-width: 3;
        }

        h1 {
            color: #126E82;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .message {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .info-box {
            background: #f0f9ff;
            border: 2px solid #126E82;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .info-box h3 {
            color: #126E82;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .info-box ul {
            list-style: none;
            padding: 0;
        }

        .info-box li {
            padding: 8px 0;
            color: #374151;
            font-size: 0.95rem;
        }

        .info-box li:before {
            content: "✓ ";
            color: #16a34a;
            font-weight: bold;
            margin-right: 8px;
        }

        .application-id-box {
            background: #dcfce7;
            border: 2px solid #16a34a;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .application-id-box h3 {
            color: #16a34a;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .application-id-box .id-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #15803d;
            margin: 10px 0;
            letter-spacing: 2px;
        }

        .application-id-box .id-note {
            font-size: 0.9rem;
            color: #374151;
            margin: 0;
        }

        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #126E82;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0e5a6b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(18, 110, 130, 0.4);
        }

        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
        }

        .logo {
            width: 80px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <img src="../assets/image/logo.png" alt="Navi Shipping Logo" class="logo">
        
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1>Application Submitted Successfully!</h1>
        
        <div class="application-id-box">
            <h3>Your Application ID</h3>
            <div class="id-number"><?php echo htmlspecialchars($application_id); ?></div>
            <p class="id-note">Please save this ID for future reference</p>
        </div>
        
        <p class="message">
            Thank you for applying to Navi Shipping. Your application has been received and is now under review.
        </p>

        <div class="info-box">
            <h3>What happens next?</h3>
            <ul>
                <li>Our HR team will review your application within 3-5 business days</li>
                <li>You will receive an email notification about your application status</li>
                <li>If shortlisted, we will contact you for an interview</li>
                <li>Keep your contact information updated</li>
            </ul>
        </div>

        <div class="info-box">
            <h3>Important Reminders:</h3>
            <ul>
                <li>Check your email regularly for updates</li>
                <li>Prepare your original documents for verification</li>
                <li>Ensure all certificates are valid and up-to-date</li>
            </ul>
        </div>

        <div class="btn-container">
            <a href="login.php" class="btn btn-primary">Go to Crew Login</a>
            <a href="apply.php" class="btn btn-secondary">Submit Another Application</a>
        </div>
    </div>
</body>
</html>
