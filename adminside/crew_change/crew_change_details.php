<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

$change_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($change_id <= 0) {
    $_SESSION['error_message'] = 'Invalid crew change ID.';
    header('Location: ../rep.php');
    exit();
}

$messagesByGroup = [
    'crew_remarks' => [],
    'crew_answer' => [],
    'candidate_remarks' => [],
    'candidate_questions' => [],
    'candidate_answer' => []
];

$changeData = [
    'id' => $change_id,
    'vessel_name' => '',
    'position_name' => '',
    'crew_to_be_replaced' => '',
    'license_required' => '',
    'replacement_name' => '',
    'replacement_license' => '',
    'status_type' => 'will_disembark',
    'date_joined' => null,
    'end_of_coe' => null,
    'end_of_extension' => null,
    'contact_number' => '',
    'target_joining_date' => null,
    'place_of_joining' => ''
];

$uploadedRelieve = [];
$uploadedExtension = [];

try {
    $db = Database::getInstance();

    $db->execute(
        "CREATE TABLE IF NOT EXISTS crew_changes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vessel_name VARCHAR(255) NOT NULL,
            position_name VARCHAR(255) NOT NULL,
            crew_to_be_replaced VARCHAR(255) NOT NULL,
            license_required VARCHAR(255) NOT NULL,
            replacement_name VARCHAR(255) NOT NULL,
            replacement_license VARCHAR(255) NOT NULL,
            status_type ENUM('will_disembark','will_extend','for_deployment') NOT NULL DEFAULT 'will_disembark',
            date_joined DATE NULL,
            end_of_coe DATE NULL,
            end_of_extension DATE NULL,
            contact_number VARCHAR(100) NULL,
            target_joining_date DATE NULL,
            place_of_joining VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $db->execute(
        "CREATE TABLE IF NOT EXISTS crew_change_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            change_id VARCHAR(50) NOT NULL,
            message_group ENUM('crew_remarks', 'crew_answer', 'candidate_remarks', 'candidate_questions', 'candidate_answer') NOT NULL,
            message_text TEXT NOT NULL,
            sender_user_id INT NULL,
            sender_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_change_group (change_id, message_group),
            INDEX idx_created_at (created_at),
            CONSTRAINT fk_ccm_user
                FOREIGN KEY (sender_user_id) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $db->execute(
        "CREATE TABLE IF NOT EXISTS crew_change_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            change_id INT NOT NULL,
            file_group ENUM('relieve','extension') NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT NULL,
            file_type VARCHAR(120) NULL,
            uploaded_by INT NULL,
            uploader_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_change_group_created (change_id, file_group, created_at),
            CONSTRAINT fk_ccf_change FOREIGN KEY (change_id) REFERENCES crew_changes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $foundChange = $db->fetchOne("SELECT * FROM crew_changes WHERE id = ?", [$change_id]);
    if ($foundChange) {
        $changeData = array_merge($changeData, $foundChange);
    } else {
        $_SESSION['error_message'] = 'Crew change record not found.';
        header('Location: ../rep.php');
        exit();
    }

    $rows = $db->fetchAll(
        "SELECT message_group, message_text, sender_name, created_at
         FROM crew_change_messages
         WHERE change_id = ?
         ORDER BY created_at ASC, id ASC",
        [(string)$change_id]
    );

    foreach ($rows as $row) {
        $group = $row['message_group'] ?? '';
        if (isset($messagesByGroup[$group])) {
            $messagesByGroup[$group][] = $row;
        }
    }

    $uploadedRelieve = $db->fetchAll(
        "SELECT id, file_name, file_path, uploader_name, created_at
         FROM crew_change_files
         WHERE change_id = ? AND file_group = 'relieve'
         ORDER BY created_at DESC, id DESC",
        [$change_id]
    );

    $uploadedExtension = $db->fetchAll(
        "SELECT id, file_name, file_path, uploader_name, created_at
         FROM crew_change_files
         WHERE change_id = ? AND file_group = 'extension'
         ORDER BY created_at DESC, id DESC",
        [$change_id]
    );

} catch (Exception $e) {
    if (defined('DB_DEBUG') && DB_DEBUG) {
        $_SESSION['error_message'] = 'Load data error: ' . $e->getMessage();
    }
}

function renderMessages(array $messages): string
{
    if (count($messages) === 0) {
        return '<div class="chat-empty">No messages yet.</div>';
    }

    $html = '';
    foreach ($messages as $m) {
        $sender = htmlspecialchars($m['sender_name'] ?? 'User');
        $text = nl2br(htmlspecialchars($m['message_text'] ?? ''));
        $time = '';
        if (!empty($m['created_at'])) {
            $time = date('m/d/Y g:i A', strtotime($m['created_at']));
        }
        $html .= '<div class="chat-item">';
        $html .= '<div class="chat-meta"><strong>' . $sender . '</strong>' . ($time ? ' <span class="chat-time">• ' . htmlspecialchars($time) . '</span>' : '') . '</div>';
        $html .= '<div class="chat-text">' . $text . '</div>';
        $html .= '</div>';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Change Status Details</title>
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/content.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="crew_change_details.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard">
        <?php
        $GLOBALS['base_path'] = '../../';
        $GLOBALS['nav_path']  = '../';
        include '../../includes/sidebar.php';
        ?>

        <main class="main">
            <div class="main__content">
                <h2 class="page-title">CREW CHANGE STATUS</h2>

                <?php if (!empty($_SESSION['success_message'])): ?>
                    <div style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:10px 12px;border-radius:8px;margin-bottom:12px;">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error_message'])): ?>
                    <div style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:10px 12px;border-radius:8px;margin-bottom:12px;">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="card card--padded change-card">
                    <div class="change-header">
                        <div class="status-info">
                            <?php
                                $statusType = (string)$changeData['status_type'];
                                $statusClass = 'status-red';
                                if ($statusType === 'will_extend') {
                                    $statusClass = 'status-blue';
                                } elseif ($statusType === 'for_deployment') {
                                    $statusClass = 'status-green';
                                }
                            ?>
                            <span class="status-value-header <?php echo $statusClass; ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $statusType))); ?></span>
                        </div>
                        <div class="change-actions">
                            <button class="btn-action btn-edit" onclick="window.location.href='crew_change_form.php?id=<?php echo (int)$change_id; ?>'">EDIT</button>
                            <form method="POST" action="delete_change.php" onsubmit="return confirm('Delete this crew change record?');" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo (int)$change_id; ?>">
                                <button class="btn-action btn-delete" type="submit">DELETE</button>
                            </form>
                            <button class="btn-close" onclick="window.location.href='../rep.php'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="change-section">
                        <h3 class="section-title">CREW INFORMATION</h3>
                        <div class="section-content-grid">
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Position</span>
                                    <span class="info-value"><?php echo htmlspecialchars((string)$changeData['position_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Crew to be Replaced</span>
                                    <span class="info-value"><?php echo htmlspecialchars((string)$changeData['crew_to_be_replaced']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">License Required</span>
                                    <span class="info-value"><?php echo htmlspecialchars((string)$changeData['license_required']); ?></span>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Date Joined</span>
                                    <span class="info-value"><?php echo !empty($changeData['date_joined']) ? date('m-d-Y', strtotime((string)$changeData['date_joined'])) : ''; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">End of COE</span>
                                    <span class="info-value"><?php echo !empty($changeData['end_of_coe']) ? date('m-d-Y', strtotime((string)$changeData['end_of_coe'])) : ''; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">End of Extension</span>
                                    <span class="info-value"><?php echo !empty($changeData['end_of_extension']) ? date('m-d-Y', strtotime((string)$changeData['end_of_extension'])) : ''; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="remarks-section">
                            <div class="remarks-header">
                                <span class="remarks-label">Remarks</span>
                            </div>
                            <form method="POST" action="save_message.php" class="chat-form">
                                <input type="hidden" name="change_id" value="<?php echo htmlspecialchars($change_id); ?>">
                                <input type="hidden" name="message_group" value="crew_remarks">
                                <div class="remarks-content">
                                    <textarea class="remarks-textarea" name="message_text" rows="3" placeholder="Enter remarks here..." required></textarea>
                                </div>
                                <button type="submit" class="btn-upload" style="margin-top:10px;">Send</button>
                            </form>
                            <div class="chat-thread"><?php echo renderMessages($messagesByGroup['crew_remarks']); ?></div>
                        </div>

                        <div class="answer-section">
                            <div class="answer-label">Answer</div>
                            <form method="POST" action="save_message.php" class="chat-form">
                                <input type="hidden" name="change_id" value="<?php echo htmlspecialchars($change_id); ?>">
                                <input type="hidden" name="message_group" value="crew_answer">
                                <div class="answer-content">
                                    <textarea class="answer-textarea" name="message_text" rows="3" placeholder="Enter answer here..." required></textarea>
                                </div>
                                <button type="submit" class="btn-upload" style="margin-top:10px;">Send</button>
                            </form>
                            <div class="chat-thread"><?php echo renderMessages($messagesByGroup['crew_answer']); ?></div>
                        </div>

                        <div class="upload-buttons">
                            <div class="upload-group">
                                <span class="upload-label">Request For Relieve</span>
                                <form method="POST" action="upload_change_file.php" enctype="multipart/form-data" class="mini-upload-form">
                                    <input type="hidden" name="change_id" value="<?php echo (int)$change_id; ?>">
                                    <input type="hidden" name="file_group" value="relieve">
                                    <input type="file" name="upload_file" required class="hidden-file-input" id="relieveFileInput" onchange="document.getElementById('relieveUploadBtn').click()">
                                    <button class="btn-upload" type="button" onclick="document.getElementById('relieveFileInput').click()">Upload Files</button>
                                    <button class="btn-upload btn-hidden-submit" id="relieveUploadBtn" type="submit">Upload</button>
                                </form>
                            </div>
                            <div class="upload-group">
                                <span class="upload-label">Request For Extension</span>
                                <form method="POST" action="upload_change_file.php" enctype="multipart/form-data" class="mini-upload-form">
                                    <input type="hidden" name="change_id" value="<?php echo (int)$change_id; ?>">
                                    <input type="hidden" name="file_group" value="extension">
                                    <input type="file" name="upload_file" required class="hidden-file-input" id="extensionFileInput" onchange="document.getElementById('extensionUploadBtn').click()">
                                    <button class="btn-upload" type="button" onclick="document.getElementById('extensionFileInput').click()">Upload Files</button>
                                    <button class="btn-upload btn-hidden-submit" id="extensionUploadBtn" type="submit">Upload</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="change-section">
                        <h3 class="section-title">RELIEVE CANDIDATE</h3>
                        <div class="section-content-grid">
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Candidates</span>
                                    <span class="info-value"><?php echo htmlspecialchars((string)$changeData['replacement_name']); ?></span>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">License</span>
                                    <span class="info-value"><?php echo htmlspecialchars((string)$changeData['replacement_license']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Contact Number</span>
                                    <span class="info-value"><?php echo htmlspecialchars((string)$changeData['contact_number']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="remarks-section">
                            <div class="remarks-header">
                                <span class="remarks-label">Candidate Remarks</span>
                            </div>
                            <form method="POST" action="save_message.php" class="chat-form">
                                <input type="hidden" name="change_id" value="<?php echo htmlspecialchars($change_id); ?>">
                                <input type="hidden" name="message_group" value="candidate_remarks">
                                <div class="remarks-content">
                                    <textarea class="remarks-textarea" name="message_text" rows="3" placeholder="Enter candidate remarks here..." required></textarea>
                                </div>
                                <button type="submit" class="btn-upload" style="margin-top:10px;">Send</button>
                            </form>
                            <div class="chat-thread"><?php echo renderMessages($messagesByGroup['candidate_remarks']); ?></div>
                        </div>

                        <div class="answer-section">
                            <div class="answer-label">Questions/Remarks</div>
                            <form method="POST" action="save_message.php" class="chat-form">
                                <input type="hidden" name="change_id" value="<?php echo htmlspecialchars($change_id); ?>">
                                <input type="hidden" name="message_group" value="candidate_questions">
                                <div class="answer-content">
                                    <textarea class="answer-textarea" name="message_text" rows="3" placeholder="Enter questions or remarks here..." required></textarea>
                                </div>
                                <button type="submit" class="btn-upload" style="margin-top:10px;">Send</button>
                            </form>
                            <div class="chat-thread"><?php echo renderMessages($messagesByGroup['candidate_questions']); ?></div>
                        </div>

                        <div class="answer-section">
                            <div class="answer-label">Answer</div>
                            <form method="POST" action="save_message.php" class="chat-form">
                                <input type="hidden" name="change_id" value="<?php echo htmlspecialchars($change_id); ?>">
                                <input type="hidden" name="message_group" value="candidate_answer">
                                <div class="answer-content">
                                    <textarea class="answer-textarea" name="message_text" rows="3" placeholder="Enter answer here..." required></textarea>
                                </div>
                                <button type="submit" class="btn-upload" style="margin-top:10px;">Send</button>
                            </form>
                            <div class="chat-thread"><?php echo renderMessages($messagesByGroup['candidate_answer']); ?></div>
                        </div>
                    </div>

                    <div class="change-section">
                        <h3 class="section-title">UPLOADED FILES</h3>
                        <div class="section-content-grid">
                            <div>
                                <div class="answer-label">Request For Relieve Files</div>
                                <div class="file-thread compact-thread">
                                    <?php if (count($uploadedRelieve) === 0): ?>
                                        <div class="chat-empty">No files uploaded yet.</div>
                                    <?php else: ?>
                                        <?php foreach ($uploadedRelieve as $file): ?>
                                            <div class="file-bubble">
                                                <a href="../../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="file-link"><?php echo htmlspecialchars($file['file_name']); ?></a>
                                                <div class="file-meta"><?php echo htmlspecialchars($file['uploader_name']); ?> • <?php echo date('m/d/Y g:i A', strtotime($file['created_at'])); ?></div>
                                                <button type="button" class="btn-file-view" onclick="openFilePreview('../../<?php echo htmlspecialchars($file['file_path']); ?>','<?php echo htmlspecialchars(addslashes($file['file_name'])); ?>')">View</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <div class="answer-label">Request For Extension Files</div>
                                <div class="file-thread compact-thread">
                                    <?php if (count($uploadedExtension) === 0): ?>
                                        <div class="chat-empty">No files uploaded yet.</div>
                                    <?php else: ?>
                                        <?php foreach ($uploadedExtension as $file): ?>
                                            <div class="file-bubble">
                                                <a href="../../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="file-link"><?php echo htmlspecialchars($file['file_name']); ?></a>
                                                <div class="file-meta"><?php echo htmlspecialchars($file['uploader_name']); ?> • <?php echo date('m/d/Y g:i A', strtotime($file['created_at'])); ?></div>
                                                <button type="button" class="btn-file-view" onclick="openFilePreview('../../<?php echo htmlspecialchars($file['file_path']); ?>','<?php echo htmlspecialchars(addslashes($file['file_name'])); ?>')">View</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="change-section">
                        <h3 class="section-title">JOINING INFORMATION</h3>
                        <div class="section-content-grid">
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Target Joining Date</span>
                                    <span class="info-value"><?php echo !empty($changeData['target_joining_date']) ? date('m-d-Y', strtotime((string)$changeData['target_joining_date'])) : ''; ?></span>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Place of Joining</span>
                                    <span class="info-value"><?php echo htmlspecialchars((string)$changeData['place_of_joining']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <div id="filePreviewModal" class="file-preview-modal" style="display:none;">
        <div class="file-preview-box">
            <div class="file-preview-header">
                <strong id="filePreviewTitle">File Preview</strong>
                <button type="button" class="btn-close-preview" onclick="closeFilePreview()">×</button>
            </div>
            <div class="file-preview-body">
                <iframe id="filePreviewFrame" src="" title="File Preview"></iframe>
            </div>
        </div>
    </div>

    <script>
        function openFilePreview(fileUrl, title) {
            document.getElementById('filePreviewTitle').textContent = title || 'File Preview';
            document.getElementById('filePreviewFrame').src = fileUrl;
            document.getElementById('filePreviewModal').style.display = 'flex';
        }

        function closeFilePreview() {
            document.getElementById('filePreviewModal').style.display = 'none';
            document.getElementById('filePreviewFrame').src = '';
        }

        window.addEventListener('click', function(e){
            const modal = document.getElementById('filePreviewModal');
            if (e.target === modal) closeFilePreview();
        });
    </script>
</body>
</html>
