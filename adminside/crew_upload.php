<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database config
require_once '../config/database.php';

try {
    $db = Database::getInstance();
    
    $crewList = $db->fetchAll("SELECT crew_no, CONCAT(first_name, ' ', last_name) as full_name FROM crew_master ORDER BY crew_no DESC");
    
    $selected_crew_no   = $_GET['crew_no'] ?? '';
    $selected_crew_name = '';
    
    if ($selected_crew_no) {
        $crew = $db->fetchOne("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM crew_master WHERE crew_no = ?", [$selected_crew_no]);
        $selected_crew_name = $crew['full_name'] ?? '';
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files - Crew Management</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/crew_upload_page.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard">
        <?php
        $GLOBALS['base_path'] = '../';
        $GLOBALS['nav_path']  = '';
        include '../includes/sidebar.php';
        ?>

        <main class="main">
            <div class="main__content">
                <h2 class="page-title">CREW MANAGEMENT</h2>

                <div class="card card--padded upload-card">
                    <!-- Header -->
                    <div class="upload-header">
                        <div class="upload-header-left">
                            <h3 class="upload-title">Upload Files</h3>
                            <p class="upload-subtitle">Upload multiple documents for each client</p>
                            
                            <div class="crew-selection" style="margin-top: 12px;">
                                <label style="font-size: 0.85rem; color: #666; margin-right: 8px;">Select Crew (Optional):</label>
                                <select id="crewSelect" class="crew-select-dropdown" onchange="selectCrew(this.value)" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.85rem; min-width: 250px;">
                                    <option value="">-- Auto-detect from filename --</option>
                                    <?php foreach ($crewList as $crew): ?>
                                        <option value="<?php echo htmlspecialchars($crew['crew_no']); ?>" 
                                            <?php echo $selected_crew_no === $crew['crew_no'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($crew['crew_no'] . ' - ' . $crew['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p style="font-size: 0.75rem; color: #999; margin-top: 6px; margin-bottom: 0;">
                                    Reminder: Include crew number (e.g., "001"), crew_no, first name, or last name in your filename for automatic identification
                                </p>
                            </div>
                        </div>
                        <div class="upload-header-right">
                            <button class="btn-remove-all" onclick="removeAllFiles()">Remove All</button>
                            <button class="btn-close-page" onclick="window.location.href='crew.php'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Upload Sections Container -->
                    <div class="upload-sections-container">
                                                <div class="upload-section">
                            <div class="upload-section-header">201 FILE</div>
                            <div class="upload-section-content">
                                <button class="choose-file-btn" onclick="document.getElementById('file201Input').click()">Choose File</button>
                                <input type="file" id="file201Input" class="file-input-hidden" accept=".pdf,.doc,.docx" multiple onchange="handleFileSelect(event, 'file201')">
                                <div class="upload-file-list" id="file201FileList"></div>
                            </div>
                        </div>
                        
                        <div class="upload-section">
                            <div class="upload-section-header">MEDICAL CERTIFICATES</div>
                            <div class="upload-section-content">
                                <button class="choose-file-btn" onclick="document.getElementById('medicalInput').click()">Choose File</button>
                                <span class="expiry-header">Expiration Date:</span>
                                <input type="file" id="medicalInput" class="file-input-hidden" accept=".pdf,.doc,.docx" multiple onchange="handleFileSelect(event, 'medical')">
                                <div class="upload-file-list" id="medicalFileList"></div>
                            </div>
                        </div>

                        <div class="upload-section">
                            <div class="upload-section-header">CONTRACT FILE</div>
                            <div class="upload-section-content">
                                <button class="choose-file-btn" onclick="document.getElementById('contractInput').click()">Choose File</button>
                                <span class="expiry-header">Expiration Date:</span>
                                <input type="file" id="contractInput" class="file-input-hidden" accept=".pdf,.doc,.docx" multiple onchange="handleFileSelect(event, 'contract')">
                                <div class="upload-file-list" id="contractFileList"></div>
                            </div>
                        </div>

                        <div class="upload-section">
                            <div class="upload-section-header">YELLOW FEVER</div>
                            <div class="upload-section-content">
                                <button class="choose-file-btn" onclick="document.getElementById('yellowfeverInput').click()">Choose File</button>
                                <span class="expiry-header">Expiration Date:</span>
                                <input type="file" id="yellowfeverInput" class="file-input-hidden" accept=".pdf,.doc,.docx" multiple onchange="handleFileSelect(event, 'yellowfever')">
                                <div class="upload-file-list" id="yellowfeverFileList"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload All Button -->
                    <div class="upload-footer">
                        <button class="upload-all-btn" onclick="uploadAllFiles()">Upload All</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
        let selectedFiles = { medical: [], contract: [], file201: [], yellowfever: [] };
        
        function selectCrew(crewNo) {
            if (crewNo) window.location.href = 'crew_upload.php?crew_no=' + crewNo;
        }
        
        function handleFileSelect(event, category) {
            const files = event.target.files;
            const fileList = document.getElementById(category + 'FileList');
            if (files.length > 0) {
                Array.from(files).forEach(file => {
                    selectedFiles[category].push(file);
                    addFileToList(file, category, fileList);
                });
                event.target.value = '';
            }
        }
        
        function addFileToList(file, category, fileList) {
            const fileRow = document.createElement('div');
            fileRow.className = 'upload-file-row';
            fileRow.dataset.fileName = file.name;
            fileRow.dataset.category = category;
            const needsExpiry = (category === 'medical' || category === 'contract' || category === 'yellowfever');
            fileRow.innerHTML = `
                <div class="upload-file-info">
                    <span class="upload-file-status">File uploaded:</span>
                    <span class="upload-file-name">${file.name}</span>
                </div>
                <div class="upload-file-actions">
                    ${needsExpiry ? `<input type="date" class="expiry-date-input" data-category="${category}" data-filename="${file.name}">` : ''}
                    <button class="upload-file-remove" onclick="removeFile(this, '${category}', '${file.name}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>`;
            fileList.appendChild(fileRow);
        }
        
        function removeFile(button, category, fileName) {
            button.closest('.upload-file-row').remove();
            if (category && fileName) {
                selectedFiles[category] = selectedFiles[category].filter(f => f.name !== fileName);
            }
        }
        
        function removeAllFiles() {
            if (confirm('Are you sure you want to remove all selected files?')) {
                ['medical', 'contract', 'file201', 'yellowfever'].forEach(category => {
                    document.getElementById(category + 'FileList').querySelectorAll('.upload-file-row[data-file-name]').forEach(f => f.remove());
                });
                selectedFiles = { medical: [], contract: [], file201: [], yellowfever: [] };
            }
        }
        
        function uploadAllFiles() {
            const crewNo  = document.getElementById('crewSelect').value;
            const allFiles = [...selectedFiles.medical, ...selectedFiles.contract, ...selectedFiles.file201, ...selectedFiles.yellowfever];
            
            if (allFiles.length === 0) { alert('No new files selected to upload.'); return; }
            
            if (!crewNo) {
                const proceed = confirm(
                    'No crew member selected. The system will automatically identify crew members from filenames.\n\n' +
                    'Make sure your filenames include:\n- Crew number (e.g., "001")\n- Crew_no (e.g., "CRW-2025-001")\n- First name or Last name\n\nContinue with upload?'
                );
                if (!proceed) return;
            }
            
            const formData = new FormData();
            if (crewNo) formData.append('crew_no', crewNo);
            
            let missingDates = [];
            Object.keys(selectedFiles).forEach(category => {
                selectedFiles[category].forEach((file, index) => {
                    const expiryInput = document.querySelector(`input[data-category="${category}"][data-filename="${file.name}"]`);
                    const expiryDate  = expiryInput ? expiryInput.value : '';
                    const needsExpiry = (category === 'medical' || category === 'contract' || category === 'yellowfever');
                    const docType = '';

                    if (needsExpiry && !expiryDate) {
                        if (category === 'file201' && docType && docType.toLowerCase() !== 'nbi') {
                            // 201 files do not require expiry except NBI
                        } else {
                            missingDates.push(file.name);
                        }
                    }

                    formData.append(category + '[]', file);
                    formData.append(category + '_expiry[]', expiryDate);
                    if (category === 'file201') {
                        formData.append(category + '_doc_type[]', docType);
                    }
                });
            });
            
            if (missingDates.length > 0) {
                alert('Please set expiration dates for the following Medical, Contract, or Yellow Fever files:\n\n' + missingDates.join('\n'));
                return;
            }

            
            
            const uploadBtn = document.querySelector('.upload-all-btn');
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Uploading...';
            
            fetch('upload_crew_documents.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let message = data.message;
                    if (data.uploaded_files?.length > 0) {
                        message += '\n\nUploaded files:';
                        data.uploaded_files.forEach(f => { message += `\n- ${f.file_name} (${f.crew_no})`; });
                    }
                    if (data.errors?.length > 0) message += '\n\nWarnings:\n' + data.errors.join('\n');
                    alert(message);
                    window.location.href = crewNo ? `crew_documents/crew_documents.php?id=${crewNo}` : 'crew.php';
                } else {
                    alert('Error: ' + data.message);
                    if (data.errors?.length > 0) alert('Errors:\n' + data.errors.join('\n'));
                }
            })
            .catch(error => alert('Upload failed: ' + error.message))
            .finally(() => { uploadBtn.disabled = false; uploadBtn.textContent = 'Upload All'; });
        }
    </script>
</body>
</html>
