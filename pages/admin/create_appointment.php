<?php
require_once __DIR__ . '/../../autoload.php';
Auth::requireAdmin('../login.php');
$user = Auth::getUser();

require_once '../includes/header.php';
require_once 'admin_nav.php';

// Load PHPMailer classes manually
require_once __DIR__ . '/../../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get donor details
        $donorQuery = "SELECT id, user_id FROM donors WHERE nic = :nic";
        $donor = Database::fetch($donorQuery, ['nic' => $_POST['donor_nic']]);
        
        if (!$donor) {
            $_SESSION['error_message'] = "Donor not found with this NIC.";
            header('Location: create_appointment.php');
            exit();
        }
        
        // Insert appointment
        $sql = "INSERT INTO appointments (
            donor_id, user_id, hospital_name, appointment_date, 
            appointment_time, appointment_type, purpose, notes,
            doctor_name, doctor_contact, created_by
        ) VALUES (:donor_id, :user_id, :hospital_name, :appointment_date,
                  :appointment_time, :appointment_type, :purpose, :notes,
                  :doctor_name, :doctor_contact, :created_by)";
        
        $params = [
            'donor_id' => $donor['id'],
            'user_id' => $donor['user_id'],
            'hospital_name' => $_POST['hospital_name'],
            'appointment_date' => $_POST['appointment_date'],
            'appointment_time' => $_POST['appointment_time'],
            'appointment_type' => 'medical_report',
            'purpose' => 'Medical Report Collection',
            'notes' => $_POST['notes'],
            'doctor_name' => $_POST['doctor_name'],
            'doctor_contact' => $_POST['doctor_contact'],
            'created_by' => $user['id']
        ];
        
        Database::execute($sql, $params);
        $appointmentId = Database::lastInsertId();
        
        // Get donor email and details for notification
        $donorDetails = Database::fetch(
            "SELECT u.email, d.first_name, d.last_name, d.contact_number
             FROM users u 
             JOIN donors d ON u.id = d.user_id 
             WHERE d.id = :donor_id",
            ['donor_id' => $donor['id']]
        );
        
        // Format date and time for display
        $appointmentDateFormatted = date('F d, Y', strtotime($_POST['appointment_date']));
        $appointmentTimeFormatted = date('h:i A', strtotime($_POST['appointment_time']));
        
        // Email details
        $to = $donorDetails['email'];
        $toName = $donorDetails['first_name'] . ' ' . $donorDetails['last_name'];
        $subject = "Medical Report Appointment Scheduled - BloodSync";
        
        // Create HTML email content
        $htmlMessage = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc2626; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
                .details { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .reminder { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; margin-top: 20px; }
                .detail-row { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #f3f4f6; }
                .detail-label { font-weight: bold; color: #666; width: 150px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📅 Appointment Confirmation</h1>
                </div>
                
                <div class="content">
                    <h2>Dear ' . htmlspecialchars($toName) . ',</h2>
                    
                    <p>A medical report collection appointment has been scheduled for you.</p>
                    
                    <div class="details">
                        <h3>Appointment Details:</h3>
                        <div class="detail-row">
                            <span class="detail-label">Date:</span>
                            ' . htmlspecialchars($appointmentDateFormatted) . '
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Time:</span>
                            ' . htmlspecialchars($appointmentTimeFormatted) . '
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Hospital:</span>
                            ' . htmlspecialchars($_POST['hospital_name']) . '
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Doctor:</span>
                            ' . htmlspecialchars($_POST['doctor_name']) . '
                        </div>';
        
        if (!empty($_POST['doctor_contact'])) {
            $htmlMessage .= '
                        <div class="detail-row">
                            <span class="detail-label">Doctor Contact:</span>
                            ' . htmlspecialchars($_POST['doctor_contact']) . '
                        </div>';
        }
        
        if (!empty($_POST['notes'])) {
            $htmlMessage .= '
                        <div class="detail-row">
                            <span class="detail-label">Notes:</span>
                            ' . nl2br(htmlspecialchars($_POST['notes'])) . '
                        </div>';
        }
        
        $htmlMessage .= '
                        <div class="detail-row" style="border-bottom: none;">
                            <span class="detail-label">Appointment ID:</span>
                            APT-' . str_pad($appointmentId, 6, '0', STR_PAD_LEFT) . '
                        </div>
                    </div>
                    
                    <div class="reminder">
                        <h3>📋 Important Reminders:</h3>
                        <ul>
                            <li>Arrive 15 minutes before your scheduled time</li>
                            <li>Bring your National Identity Card (NIC)</li>
                            <li>Bring any previous medical reports or prescriptions</li>
                            <li>Wear comfortable clothing</li>
                            <li>Contact us at least 24 hours in advance if you need to reschedule</li>
                        </ul>
                    </div>
                    
                    <p><strong>Location:</strong> ' . htmlspecialchars($_POST['hospital_name']) . '</p>
                    <p><strong>Your Contact Number:</strong> ' . htmlspecialchars($donorDetails['contact_number']) . '</p>
                    
                    <p>Best regards,<br><strong>The BloodSync Team</strong></p>
                </div>
                
                <div class="footer">
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>If you need assistance, contact us at: support@bloodsync.com</p>
                    <p>© ' . date('Y') . ' BloodSync. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Create plain text version
        $plainMessage = "Dear " . $toName . ",\n\n";
        $plainMessage .= "A medical report collection appointment has been scheduled for you.\n\n";
        $plainMessage .= "APPOINTMENT DETAILS:\n";
        $plainMessage .= "===================\n";
        $plainMessage .= "Date: " . $appointmentDateFormatted . "\n";
        $plainMessage .= "Time: " . $appointmentTimeFormatted . "\n";
        $plainMessage .= "Hospital: " . $_POST['hospital_name'] . "\n";
        $plainMessage .= "Doctor: " . $_POST['doctor_name'] . "\n";
        
        if (!empty($_POST['doctor_contact'])) {
            $plainMessage .= "Doctor Contact: " . $_POST['doctor_contact'] . "\n";
        }
        
        if (!empty($_POST['notes'])) {
            $plainMessage .= "Notes: " . $_POST['notes'] . "\n";
        }
        
        $plainMessage .= "Appointment ID: APT-" . str_pad($appointmentId, 6, '0', STR_PAD_LEFT) . "\n\n";
        $plainMessage .= "IMPORTANT REMINDERS:\n";
        $plainMessage .= "====================\n";
        $plainMessage .= "• Arrive 15 minutes before your scheduled time\n";
        $plainMessage .= "• Bring your National Identity Card (NIC)\n";
        $plainMessage .= "• Bring any previous medical reports or prescriptions\n";
        $plainMessage .= "• Wear comfortable clothing\n";
        $plainMessage .= "• Contact us at least 24 hours in advance if you need to reschedule\n\n";
        $plainMessage .= "Location: " . $_POST['hospital_name'] . "\n";
        $plainMessage .= "Your Contact Number: " . $donorDetails['contact_number'] . "\n\n";
        $plainMessage .= "Best regards,\n";
        $plainMessage .= "The BloodSync Team\n";
        $plainMessage .= "bloodsync07@gmail.com\n\n";
        $plainMessage .= "This is an automated message. Please do not reply to this email.\n";
        
        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        $emailSent = false;
        
        if (isset($_POST['send_email']) && $_POST['send_email'] == '1') {
            try {
                // Server settings
                $mail->isSMTP();                                            // Send using SMTP
                $mail->Host       = 'smtp.gmail.com';                       // Set Gmail SMTP server
                $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                $mail->Username   = 'bloodsync07@gmail.com';                 // Your Gmail address
                $mail->Password   = 'awka jikk sorz fgjc';                  // Your Gmail app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
                $mail->Port       = 587;                                    // TCP port to connect to
                
                // Recipients
                $mail->setFrom('bloodsync07@gmail.com', 'BloodSync Appointments');
                $mail->addAddress($to, $toName);                            // Add a recipient
                $mail->addReplyTo('bloodsync07@gmail.com', 'BloodSync Support');
                
                // Content
                $mail->isHTML(true);                                        // Set email format to HTML
                $mail->Subject = $subject;
                $mail->Body    = $htmlMessage;
                $mail->AltBody = $plainMessage;                             // Plain text for non-HTML mail clients
                
                // Send email
                $emailSent = $mail->send();
                
            } catch (Exception $e) {
                // Email failed, but don't stop the appointment creation
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
            }
        }
        
        // Create notification in database
        $notificationSql = "INSERT INTO notifications (user_id, type, title, message) 
                           VALUES (:user_id, 'appointment', 'New Appointment Scheduled', :message)";
        
        Database::execute($notificationSql, [
            'user_id' => $donor['user_id'],
            'message' => "A medical report appointment has been scheduled for " . $appointmentDateFormatted . " at " . $_POST['hospital_name']
        ]);
        
        // Mark as notified
        Database::execute(
            "UPDATE appointments SET notified_at = CURRENT_TIMESTAMP WHERE id = :id",
            ['id' => $appointmentId]
        );
        
        if ($emailSent) {
            $_SESSION['success_message'] = "Appointment created successfully! Email notification sent to donor.";
        } else {
            $_SESSION['success_message'] = "Appointment created successfully!";
            if (isset($_POST['send_email']) && $_POST['send_email'] == '1') {
                $_SESSION['warning_message'] = "Email could not be sent. Please check your email configuration.";
            }
        }
        
        header('Location: manage_appointments.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error creating appointment: " . $e->getMessage();
    }
}

// Get today's date for minimum date
$today = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+3 months'));
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Schedule Medical Report Appointment</h1>
        <p class="text-gray-600">Create appointment for donors to collect medical reports</p>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['success_message']; ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['warning_message'])): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['warning_message']; ?>
            <?php unset($_SESSION['warning_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['error_message']; ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="POST" action="">
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Donor NIC -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Donor NIC *</label>
                    <input type="text" name="donor_nic" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Enter donor NIC number" required>
                    <p class="text-sm text-gray-500 mt-1">Enter the donor's National Identity Card number</p>
                </div>
                
                <!-- Hospital Name -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Hospital/Clinic *</label>
                    <input type="text" name="hospital_name" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="e.g., General Hospital Colombo" required>
                </div>
                
                <!-- Appointment Date -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Appointment Date *</label>
                    <input type="date" name="appointment_date" 
                           min="<?php echo $today; ?>"
                           max="<?php echo $maxDate; ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
                </div>
                
                <!-- Appointment Time -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Appointment Time *</label>
                    <select name="appointment_time" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
                        <option value="">Select Time</option>
                        <option value="08:00">08:00 AM</option>
                        <option value="09:00">09:00 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="14:00">02:00 PM</option>
                        <option value="15:00">03:00 PM</option>
                        <option value="16:00">04:00 PM</option>
                    </select>
                </div>
                
                <!-- Doctor Name -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Doctor's Name</label>
                    <input type="text" name="doctor_name" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Optional">
                </div>
                
                <!-- Doctor Contact -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Doctor's Contact</label>
                    <input type="text" name="doctor_contact" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Optional phone number">
                </div>
                
                <!-- Notes -->
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Additional Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Any special instructions or requirements for the donor..."></textarea>
                </div>
                
                <!-- Email Notification Option -->
                <div class="md:col-span-2">
                    <div class="flex items-center">
                        <input type="checkbox" name="send_email" value="1" checked 
                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded mr-2">
                        <div>
                            <span class="text-gray-700 font-medium">Send email notification</span>
                            <p class="text-sm text-gray-500 mt-1">
                                Send appointment details to donor's email address using PHPMailer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end space-x-4">
                <a href="manage_appointments.php" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                    Schedule Appointment
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>