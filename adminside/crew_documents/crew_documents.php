<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

$crew_no   = isset($_GET['id'])   ? $_GET['id']   : '';
$crew_name = isset($_GET['name']) ? $_GET['name'] : '';
$allDocs   = [];
$statusFilter = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : 'active';
$allowedStatusFilters = ['all', 'active', 'archived'];
if (!in_array($statusFilter, $allowedStatusFilters, true)) {
    $statusFilter = 'active';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['archive_doc_id']) || isset($_POST['restore_doc_id']))) {
    $archiveDocId = (int)($_POST['archive_doc_id'] ?? 0);
    $restoreDocId = (int)($_POST['restore_doc_id'] ?? 0);
    $postCrewNo = trim((string)($_POST['crew_no'] ?? $crew_no));
    $postStatus = strtolower(trim((string)($_POST['status'] ?? $statusFilter)));
    if (!in_array($postStatus, $allowedStatusFilters, true)) {
        $postStatus = 'active';
    }

    try {
        $db = Database::getInstance();

        if ($archiveDocId > 0) {
            $updated = $db->execute(
                "UPDATE crew_documents SET status = 'archived' WHERE id = ? AND crew_no = ? AND status = 'active'",
                [$archiveDocId, $postCrewNo]
            );

            if ($updated) {
                $_SESSION['flash_success'] = 'Document archived successfully.';
            } else {
                $_SESSION['flash_error'] = 'Unable to archive document or document already archived.';
            }
        } elseif ($restoreDocId > 0) {
            $updated = $db->execute(
                "UPDATE crew_documents SET status = 'active' WHERE id = ? AND crew_no = ? AND status = 'archived'",
                [$restoreDocId, $postCrewNo]
            );

            if ($updated) {
                $_SESSION['flash_success'] = 'Document restored successfully.';
            } else {
                $_SESSION['flash_error'] = 'Unable to restore document.';
            }
        } else {
            $_SESSION['flash_error'] = 'Invalid document action.';
        }
    } catch (Exception $e) {
        error_log("Error updating crew document status: " . $e->getMessage());
        $_SESSION['flash_error'] = 'Failed to update document status.';
    }

    $redirectUrl = 'crew_documents.php';
    $query = [];
    if ($postCrewNo !== '') {
        $query['id'] = $postCrewNo;
    }
    if ($postStatus !== '') {
        $query['status'] = $postStatus;
    }
    if (!empty($query)) {
        $redirectUrl .= '?' . http_build_query($query);
    }

    header('Location: ' . $redirectUrl);
    exit();
}

try {
    $db = Database::getInstance();
    
    // Fallback: if id is missing, use latest viewed crew from session (set by document_preview.php)
    if (empty($crew_no) && !empty($_SESSION['last_crew_no'])) {
        $crew_no = (string)$_SESSION['last_crew_no'];
    }

    if (empty($crew_name) && !empty($crew_no)) {
        $crew = $db->fetchOne("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM crew_master WHERE crew_no = ?", [$crew_no]);
        $crew_name = $crew['full_name'] ?? 'Unknown';
    }
    
    $documents = [];
    if (!empty($crew_no)) {
        $sql = "SELECT * FROM crew_documents WHERE crew_no = ?";
        $params = [$crew_no];

        if ($statusFilter === 'active' || $statusFilter === 'archived') {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY upload_date DESC";

        $allDocs = $db->fetchAll($sql, $params) ?: [];
        foreach ($allDocs as $doc) {
            $documents[$doc['document_category']][] = $doc;
        }
    }
    
} catch (Exception $e) {
    error_log("Error fetching crew documents: " . $e->getMessage());
    $documents = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Documents - <?php echo htmlspecialchars($crew_name); ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/content.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="crew_documents.css?v=<?php echo time(); ?>">
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
                <h2 class="page-title">CREW DOCUMENTS</h2>

                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div style="margin-bottom: 12px; padding: 10px 14px; border-radius: 8px; background: #ecfdf3; color: #166534; border: 1px solid #bbf7d0;">
                        <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['flash_error'])): ?>
                    <div style="margin-bottom: 12px; padding: 10px 14px; border-radius: 8px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">
                        <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
                    </div>
                <?php endif; ?>

                <div class="card card--padded documents-card">
                    <!-- Header -->
                    <div class="documents-header">
                        <div class="crew-info">
                            <span class="crew-id"><?php echo htmlspecialchars($crew_no); ?></span>
                            <span class="crew-name"><?php echo htmlspecialchars($crew_name); ?></span>
                        </div>
                        <div class="documents-actions">
                            <button class="btn-action btn-edit">EDIT</button>
                            <button class="btn-action btn-delete">DELETE</button>
                            <button class="btn-close" onclick="window.location.href='../crew.php'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="documents-toolbar">
                        <h3 class="overall-title">201 FILE</h3>
                        <div class="documents-filters">
                            <div class="search-wrap">
                                <input type="search" id="docSearch" class="input-search" placeholder="Search file name or category...">
                            </div>
                            <select id="docCategoryFilter" class="filter-select">
                                <option value="all">All</option>
                                <option value="certificates">Certificates</option>
                                <option value="seaman_book">Seaman book</option>
                                <option value="sirb">SIRB</option>
                                <option value="d_coc">D-COC</option>
                                <option value="sid">SID</option>
                                <option value="marina_license_id">Marina License ID</option>
                                <option value="passport">Passport</option>
                                <option value="sea_services">Sea Services</option>
                                <option value="nbi">NBI</option>
                                <option value="philhealth">Philhealth</option>
                                <option value="sss">SSS</option>
                                <option value="pagibig">Pagibig</option>
                                <option value="tin_id">TIN ID</option>
                                <option value="bank_details">Bank details</option>
                                <option value="medical_certificate">Medical</option>
                                <option value="contract_file">Contract</option>
                                <option value="embarkation_file">Embarkation</option>
                                <option value="yellow_fever">Yellow Fever</option>
                            </select>
                            <select id="docStatusFilter" class="filter-select">
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                            </select>
                        </div>
                    </div>

                    <!-- Documents Table -->
                    <div class="documents-table-wrap">
                        <table class="documents-table">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th>File Name</th>
                                    <th>Uploaded At</th>
                                    <th>Expiration Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="documentsList">
                                <?php
                                $categories = [
                                    'medical_certificate' => 'MEDICAL CERTIFICATE',
                                    'contract_file'       => 'CONTRACT FILE',
                                    'embarkation_file'    => 'EMBARKATION FILE',
                                    'certificates'        => 'CERTIFICATES',
                                    'seaman_book'         => 'SEAMAN BOOK',
                                    'd_coc'               => 'D-COC',
                                    'sid'                 => 'SID',
                                    'marina_license_id'   => 'MARINA LICENSE ID',
                                    'philhealth'          => 'PHILHEALTH',
                                    'sss'                 => 'SSS',
                                    'pagibig'             => 'PAGIBIG',
                                    'tin_id'              => 'TIN ID',
                                    'bank_details'        => 'BANK DETAILS'
                                ];

                                $hasRows = false;

                                foreach ($allDocs as $doc):
                                    $category = trim((string)($doc['document_category'] ?? ''));
                                    $fileNameLower = strtolower((string)($doc['file_name'] ?? ''));

                                    if ($category === '') {
                                        if (strpos($fileNameLower, 'sea services') !== false || strpos($fileNameLower, 'seaservices') !== false) {
                                            $category = 'sea_services';
                                        } elseif (strpos($fileNameLower, 'passport') !== false) {
                                            $category = 'passport';
                                        } elseif (strpos($fileNameLower, 'nbi') !== false) {
                                            $category = 'nbi';
                                        } elseif (strpos($fileNameLower, 'sirb') !== false) {
                                            $category = 'sirb';
                                        } elseif (strpos($fileNameLower, 'seaman book') !== false || strpos($fileNameLower, 'seamanbook') !== false) {
                                            $category = 'seaman_book';
                                        } elseif (strpos($fileNameLower, 'd-coc') !== false || strpos($fileNameLower, 'dcoc') !== false) {
                                            $category = 'd_coc';
                                        } elseif (strpos($fileNameLower, 'sid') !== false) {
                                            $category = 'sid';
                                        } elseif (strpos($fileNameLower, 'marina') !== false) {
                                            $category = 'marina_license_id';
                                        } elseif (strpos($fileNameLower, 'philhealth') !== false) {
                                            $category = 'philhealth';
                                        } elseif (strpos($fileNameLower, 'sss') !== false) {
                                            $category = 'sss';
                                        } elseif (strpos($fileNameLower, 'pagibig') !== false) {
                                            $category = 'pagibig';
                                        } elseif (strpos($fileNameLower, 'tin') !== false) {
                                            $category = 'tin_id';
                                        } elseif (strpos($fileNameLower, 'bank') !== false) {
                                            $category = 'bank_details';
                                        } elseif (strpos($fileNameLower, 'certificate') !== false) {
                                            $category = 'certificates';
                                        } elseif (strpos($fileNameLower, 'medical') !== false) {
                                            $category = 'medical_certificate';
                                        } elseif (strpos($fileNameLower, 'contract') !== false) {
                                            $category = 'contract_file';
                                        } elseif (strpos($fileNameLower, 'embark') !== false) {
                                            $category = 'embarkation_file';
                                        } elseif (
                                            strpos($fileNameLower, 'yellow fever') !== false ||
                                            strpos($fileNameLower, 'yellowfever') !== false ||
                                            strpos($fileNameLower, 'yellow_fever') !== false
                                        ) {
                                            $category = 'yellow_fever';
                                        } else {
                                            $category = 'file_201';
                                        }
                                    }

                                    $title = $categories[$category] ?? '201 FILE';

                                    $selectedFilter = isset($_GET['filter']) ? strtolower(trim((string)$_GET['filter'])) : '';
                                    if ($selectedFilter !== '' && $selectedFilter !== 'all' && $selectedFilter !== strtolower($category)) {
                                        continue;
                                    }

                                    $hasRows = true;
                                    $showExpiry = (
                                        $category === 'medical_certificate' ||
                                        $category === 'contract_file' ||
                                        $category === 'yellow_fever' ||
                                        $category === 'nbi' ||
                                        $category === 'certificates'
                                    );
                                            $expiryDateFormatted = 'N/A';
                                            $statusText = 'N/A';
                                            $statusClass = '';

                                            if ($showExpiry && !empty($doc['expiration_date']) && $doc['expiration_date'] !== '0000-00-00') {
                                                try {
                                                    $expiryDate = new DateTime($doc['expiration_date']);
                                                    $today      = new DateTime();
                                                    $diff       = $today->diff($expiryDate);
                                                    $expiryDateFormatted = $expiryDate->format('F d, Y');

                                                    if ($expiryDate < $today) {
                                                        $statusText = 'Expired ' . $diff->days . ' days ago';
                                                        $statusClass = 'status-expired';
                                                    } elseif ($diff->days <= 30) {
                                                        $statusText = 'Expires in ' . $diff->days . ' days';
                                                        $statusClass = 'status-expiring';
                                                    } else {
                                                        $statusText = 'Valid (' . $diff->days . ' days remaining)';
                                                    }
                                                } catch (Exception $e) {
                                                    $expiryDateFormatted = 'Invalid Date';
                                                    $statusText = 'N/A';
                                                }
                                            } elseif ($showExpiry) {
                                                $statusText = 'No expiration date set';
                                            }

                                            $uploadDate = !empty($doc['upload_date']) ? date('F d, Y h:i A', strtotime($doc['upload_date'])) : 'N/A';
                                            $rawFileName = (string)($doc['file_name'] ?? '');
                                            $cleanFileName = preg_replace('/^\s*\[[^\]]+\]\s*/', '', $rawFileName);

                                            $docType = trim((string)($doc['document_type'] ?? ''));
                                            if ($docType === '') {
                                                $sourceForType = strtolower($rawFileName . ' ' . ($doc['document_category'] ?? ''));

                                                if (strpos($sourceForType, 'sea services') !== false || strpos($sourceForType, 'seaservices') !== false || strpos($sourceForType, 'sea_services') !== false) {
                                                    $docType = 'Sea Services';
                                                } elseif (strpos($sourceForType, 'passport') !== false) {
                                                    $docType = 'Passport';
                                                } elseif (strpos($sourceForType, 'nbi') !== false) {
                                                    $docType = 'NBI';
                                                } elseif (strpos($sourceForType, 'sirb') !== false) {
                                                    $docType = 'SIRB';
                                                } elseif (strpos($sourceForType, 'seaman book') !== false || strpos($sourceForType, 'seamanbook') !== false) {
                                                    $docType = 'Seaman book';
                                                } elseif (strpos($sourceForType, 'd-coc') !== false || strpos($sourceForType, 'dcoc') !== false) {
                                                    $docType = 'D-COC';
                                                } elseif (strpos($sourceForType, 'sid') !== false) {
                                                    $docType = 'SID';
                                                } elseif (strpos($sourceForType, 'marina') !== false) {
                                                    $docType = 'Marina License ID';
                                                } elseif (strpos($sourceForType, 'philhealth') !== false) {
                                                    $docType = 'Philhealth';
                                                } elseif (strpos($sourceForType, 'sss') !== false) {
                                                    $docType = 'SSS';
                                                } elseif (strpos($sourceForType, 'pagibig') !== false) {
                                                    $docType = 'Pagibig';
                                                } elseif (strpos($sourceForType, 'tin') !== false) {
                                                    $docType = 'TIN ID';
                                                } elseif (strpos($sourceForType, 'bank') !== false) {
                                                    $docType = 'Bank details';
                                                } elseif ($category === 'medical_certificate' || strpos($sourceForType, 'medical certificate') !== false) {
                                                    $docType = 'Medical Certificate';
                                                } elseif (strpos($sourceForType, 'certificate') !== false) {
                                                    $docType = 'Certificates';
                                                } elseif ($category === 'contract_file') {
                                                    $docType = 'Contract';
                                                } elseif ($category === 'embarkation_file') {
                                                    $docType = 'Embarkation';
                                                } elseif ($category === 'yellow_fever') {
                                                    $docType = 'Yellow Fever';
                                                } else {
                                                    $docType = '201 File';
                                                }
                                            }

                                            $searchText = strtolower($title . ' ' . $cleanFileName . ' ' . $docType);
                                ?>
                                    <tr class="document-row"
                                        data-category="<?php echo htmlspecialchars(strtolower($category)); ?>"
                                        data-doctype="<?php echo htmlspecialchars(strtolower($docType)); ?>"
                                        data-search="<?php echo htmlspecialchars($searchText); ?>">
                                        <td><?php echo htmlspecialchars($docType); ?></td>
                                        <td><?php echo htmlspecialchars($cleanFileName); ?></td>
                                        <td><?php echo htmlspecialchars($uploadDate); ?></td>
                                        <td><?php echo htmlspecialchars($expiryDateFormatted); ?></td>
                                        <td class="<?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusText); ?></td>
                                        <td>
                                            <div class="doc-actions">
                                                <?php $docStatus = strtolower((string)($doc['status'] ?? 'active')); ?>
                                                <?php if ($docStatus === 'archived'): ?>
                                                    <form method="POST" class="archive-form" onsubmit="return confirm('Restore this document?');">
                                                        <input type="hidden" name="restore_doc_id" value="<?php echo htmlspecialchars((string)$doc['id']); ?>">
                                                        <input type="hidden" name="crew_no" value="<?php echo htmlspecialchars((string)$crew_no); ?>">
                                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                                                        <button type="submit" class="btn-restore" title="Restore document" aria-label="Restore document">
                                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                                <path d="M9 4.5A7.5 7.5 0 0 1 19 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                                                                <path d="M19 9V5.5M19 9H15.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                                                                <path d="M15 19.5A7.5 7.5 0 0 1 5 15" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                                                                <path d="M5 15v3.5M5 15h3.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="archive-form" onsubmit="return confirm('Archive this document?');">
                                                        <input type="hidden" name="archive_doc_id" value="<?php echo htmlspecialchars((string)$doc['id']); ?>">
                                                        <input type="hidden" name="crew_no" value="<?php echo htmlspecialchars((string)$crew_no); ?>">
                                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                                                        <button type="submit" class="btn-archive" title="Archive document" aria-label="Archive document">
                                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                                <path d="M3 6.5C3 5.67 3.67 5 4.5 5H19.5C20.33 5 21 5.67 21 6.5V9C21 9.55 20.55 10 20 10H4C3.45 10 3 9.55 3 9V6.5Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                                                                <path d="M5 10V18C5 19.1 5.9 20 7 20H17C18.1 20 19 19.1 19 18V10" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                                                                <rect x="9" y="13" width="6" height="2" rx="1" stroke="currentColor" stroke-width="1.3"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="document_preview.php?doc_id=<?php echo urlencode((string)$doc['id']); ?>" class="btn-view">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                endforeach;

                                if (!$hasRows):
                                ?>
                                    <tr>
                                        <td colspan="6" class="no-documents">
                                            <p>No documents uploaded yet.</p>
                                            <p style="margin-top: 10px;">
                                                <a href="../crew_upload.php?crew_no=<?php echo urlencode($crew_no); ?>" style="color: #17a2b8; text-decoration: underline;">Upload Documents</a>
                                            </p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script>
        (function () {
            const searchInput = document.getElementById('docSearch');
            const categoryFilter = document.getElementById('docCategoryFilter');
            const statusFilter = document.getElementById('docStatusFilter');
            const rows = Array.from(document.querySelectorAll('.document-row'));

            function applyFilters() {
                const query = (searchInput?.value || '').toLowerCase().trim();
                const selectedCategory = (categoryFilter?.value || 'all').toLowerCase();

                rows.forEach(row => {
                    const category = (row.dataset.category || '').toLowerCase();
                    const searchText = (row.dataset.search || '').toLowerCase();

                    const categoryMatch = selectedCategory === 'all' || category === selectedCategory;
                    const searchMatch = !query || searchText.includes(query);

                    row.style.display = (categoryMatch && searchMatch) ? '' : 'none';
                });
            }

            if (searchInput) searchInput.addEventListener('input', applyFilters);
            if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);

            if (statusFilter) {
                statusFilter.addEventListener('change', function () {
                    const url = new URL(window.location.href);
                    url.searchParams.set('status', this.value);
                    const crewId = <?php echo json_encode((string)$crew_no); ?>;
                    if (crewId) {
                        url.searchParams.set('id', crewId);
                    }
                    window.location.href = url.toString();
                });
            }
        })();
    </script>
</body>
</html>
