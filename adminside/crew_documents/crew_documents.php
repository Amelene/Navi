<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

$crew_no   = isset($_GET['id'])   ? $_GET['id']   : '';
$crew_name = isset($_GET['name']) ? $_GET['name'] : '';

try {
    $db = Database::getInstance();
    
    if (empty($crew_name) && !empty($crew_no)) {
        $crew = $db->fetchOne("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM crew_master WHERE crew_no = ?", [$crew_no]);
        $crew_name = $crew['full_name'] ?? 'Unknown';
    }
    
    $documents = [];
    if (!empty($crew_no)) {
        $allDocs = $db->fetchAll(
            "SELECT * FROM crew_documents WHERE crew_no = ? AND status = 'active' ORDER BY upload_date DESC",
            [$crew_no]
        );
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
                                    $showExpiry = ($category === 'medical_certificate' || $category === 'contract_file' || $category === 'yellow_fever');
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
                                                <button type="button" class="btn-archive">ARCHIVE</button>
                                                <a href="document_preview.php?doc_id=<?php echo urlencode((string)$doc['id']); ?>" target="_blank" class="btn-view">VIEW</a>
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
            const rows = Array.from(document.querySelectorAll('.document-row'));

            function applyFilters() {
                const query = (searchInput?.value || '').toLowerCase().trim();
                const selected = (categoryFilter?.value || 'all').toLowerCase();

                rows.forEach(row => {
                    const category = (row.dataset.category || '').toLowerCase();
                    const searchText = (row.dataset.search || '').toLowerCase();

                    const categoryMatch = selected === 'all' || category === selected;
                    const searchMatch = !query || searchText.includes(query);

                    row.style.display = (categoryMatch && searchMatch) ? '' : 'none';
                });
            }

            if (searchInput) searchInput.addEventListener('input', applyFilters);
            if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);
        })();
    </script>
</body>
</html>
