<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa;">
    <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; background-color: #f4f7fa; padding: 20px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: #ffffff; padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0 0 10px 0; font-size: 28px; font-weight: bold;">Payment Received</h1>
                            <p style="margin: 0; font-size: 16px; opacity: 0.9;">Thank you for your membership!</p>
                        </td>
                    </tr>
                    
                    <!-- Success Icon -->
                    <tr>
                        <td style="padding: 40px 30px 20px 30px; text-align: center;">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="#ff6b35"/>
                                <path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </td>
                    </tr>
                    
                    <!-- Message -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px; text-align: center;">
                            <h2 style="margin: 0 0 10px 0; color: #333333; font-size: 24px;">Payment Successful!</h2>
                            <p style="margin: 0; color: #666666; font-size: 16px; line-height: 1.6;">Your membership has been activated and your payment has been processed successfully.</p>
                        </td>
                    </tr>
                    
                    <!-- Plan Highlight -->
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); border-radius: 8px; padding: 20px;">
                                <tr>
                                    <td style="text-align: center; color: #ffffff;">
                                        <h3 style="margin: 0 0 5px 0; font-size: 20px; font-weight: bold;">{{ $membershipPlan }}</h3>
                                        <p style="margin: 0; font-size: 14px; opacity: 0.9;">Your Selected Membership</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Payment Details -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; background-color: #fff3e0; border-radius: 8px; padding: 25px;">
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #ffe0b2;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%;">
                                            <tr>
                                                <td style="color: #666666; font-size: 14px; font-weight: 600;">Transaction ID</td>
                                                <td style="color: #333333; font-size: 14px; font-weight: 500; text-align: right;">{{ $transactionId }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                               
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e0e0e0;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%;">
                                            <tr>
                                                <td style="color: #666666; font-size: 14px; font-weight: 600;">Membership Start Date</td>
                                                <td style="color: #333333; font-size: 14px; font-weight: 500; text-align: right;">{{ $membershipStartDate }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e0e0e0;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%;">
                                            <tr>
                                                <td style="color: #666666; font-size: 14px; font-weight: 600;">Membership End Date</td>
                                                <td style="color: #333333; font-size: 14px; font-weight: 500; text-align: right;">{{ $membershipEndDate }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%;">
                                            <tr>
                                                <td style="color: #666666; font-size: 14px; font-weight: 600;">Payment Method</td>
                                                <td style="color: #333333; font-size: 14px; font-weight: 500; text-align: right;">{{ $paymentMethod }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Total Amount -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; background-color: #333333; border-radius: 8px; padding: 20px;">
                                <tr>
                                    <td style="color: #ffffff; font-size: 16px; font-weight: 600;">Total Amount Paid</td>
                                    <td style="color: #ffffff; font-size: 24px; font-weight: bold; text-align: right;">₱ {{ number_format($totalAmount, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                  
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>