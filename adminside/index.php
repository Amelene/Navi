<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$dashboardStats = [
	'total_crew' => 0,
	'on_board' => 0,
	'active_vessels' => 0
];

$overviewChartLabels = [];
$overviewChartDatasets = [];

$expiryDistribution = [
	'three_months_before' => 0,
	'two_months_before' => 0,
	'one_month_before' => 0,
	'expiring_overdue' => 0
];

$documentExpirySummary = [
	'total_expiring' => 0,
	'critical' => 0,
	'warning' => 0
];

$recentActivities = [];

$documentExpiryRows = [];
$documentExpiryPeopleCounts = [
	'critical' => 0,
	'warning' => 0,
	'normal' => 0
];

if (!isset($_SESSION['dashboard_tasks']) || !is_array($_SESSION['dashboard_tasks'])) {
	$_SESSION['dashboard_tasks'] = [
		[
			'id' => 'task_' . uniqid(),
			'title' => 'Contract Renewal - Ocean 1',
			'date' => date('Y-m-d', strtotime('+7 days')),
			'priority' => 'high'
		],
		[
			'id' => 'task_' . uniqid(),
			'title' => 'Safety Training for New Crew',
			'date' => date('Y-m-d', strtotime('+10 days')),
			'priority' => 'medium'
		]
	];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dashboard_task_action'])) {
	$action = strtolower(trim((string)$_POST['dashboard_task_action']));

	if ($action === 'add') {
		$title = trim((string)($_POST['task_title'] ?? ''));
		$date = trim((string)($_POST['task_date'] ?? ''));
		$priority = strtolower(trim((string)($_POST['task_priority'] ?? 'medium')));
		$allowedPriorities = ['high', 'medium', 'low'];

		if ($title !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			if (!in_array($priority, $allowedPriorities, true)) {
				$priority = 'medium';
			}
			$_SESSION['dashboard_tasks'][] = [
				'id' => 'task_' . uniqid(),
				'title' => $title,
				'date' => $date,
				'priority' => $priority
			];
		}
	} elseif ($action === 'delete') {
		$taskId = trim((string)($_POST['task_id'] ?? ''));
		if ($taskId !== '') {
			$_SESSION['dashboard_tasks'] = array_values(array_filter($_SESSION['dashboard_tasks'], function ($task) use ($taskId) {
				return (string)($task['id'] ?? '') !== $taskId;
			}));
		}
	}

	header('Location: index.php');
	exit();
}

$upcomingTasks = $_SESSION['dashboard_tasks'];
usort($upcomingTasks, function ($a, $b) {
	return strcmp((string)($a['date'] ?? ''), (string)($b['date'] ?? ''));
});

try {
	$db = Database::getInstance();

	$dashboardStats['total_crew'] = (int)($db->fetchOne("SELECT COUNT(*) AS count FROM crew_master")['count'] ?? 0);
	$dashboardStats['on_board'] = (int)($db->fetchOne("SELECT COUNT(*) AS count FROM crew_master WHERE crew_status = 'on_board'")['count'] ?? 0);
	$dashboardStats['active_vessels'] = (int)($db->fetchOne("SELECT COUNT(DISTINCT vessel_name) AS count FROM vw_crew_details WHERE vessel_name IS NOT NULL AND TRIM(vessel_name) <> ''")['count'] ?? 0);

	$positionOverviewRows = $db->fetchAll("
		SELECT
			COALESCE(NULLIF(TRIM(v.position_name), ''), 'UNASSIGNED') AS position_name,
			CASE
				WHEN cm.crew_no REGEXP '^CRW-[0-9]{4}-' THEN SUBSTRING(cm.crew_no, 5, 4)
				ELSE CAST(YEAR(COALESCE(cm.updated_at, cm.created_at)) AS CHAR)
			END AS record_year,
			COUNT(*) AS total_count
		FROM crew_master cm
		LEFT JOIN positions v ON v.id = cm.position_id
		WHERE cm.crew_status = 'on_board'
		GROUP BY
			COALESCE(NULLIF(TRIM(v.position_name), ''), 'UNASSIGNED'),
			record_year
		ORDER BY
			record_year ASC,
			COALESCE(NULLIF(TRIM(v.position_name), ''), 'UNASSIGNED') ASC
	");

	$chartColorPalette = ['#8979FF', '#FF928A', '#3CC3DF', '#FFAE4C', '#537FF1', '#5EC269', '#F06292', '#26A69A', '#8D6E63', '#42A5F5'];
	$yearSet = [];
	$positionYearMap = [];

	foreach ($positionOverviewRows as $row) {
		$positionName = (string)($row['position_name'] ?? 'UNASSIGNED');
		$recordYear = (string)($row['record_year'] ?? '');
		$totalCount = (int)($row['total_count'] ?? 0);

		if ($recordYear === '') {
			continue;
		}

		$yearSet[$recordYear] = true;

		if (!isset($positionYearMap[$positionName])) {
			$positionYearMap[$positionName] = [];
		}
		$positionYearMap[$positionName][$recordYear] = $totalCount;
	}

	$overviewChartLabels = array_keys($yearSet);
	sort($overviewChartLabels);

	if (!empty($overviewChartLabels)) {
		$maxYear = (int)max($overviewChartLabels);
		for ($y = $maxYear + 1; $y <= 2029; $y++) {
			$overviewChartLabels[] = (string)$y;
		}
	}

	$colorIndex = 0;
	foreach ($positionYearMap as $positionName => $yearCountMap) {
		$seriesData = [];
		foreach ($overviewChartLabels as $yearLabel) {
			$seriesData[] = (int)($yearCountMap[$yearLabel] ?? 0);
		}

		$overviewChartDatasets[] = [
			'label' => $positionName,
			'data' => $seriesData,
			'backgroundColor' => $chartColorPalette[$colorIndex % count($chartColorPalette)]
		];
		$colorIndex++;
	}

	$expiryRow = $db->fetchOne("
		SELECT
			COALESCE(SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) BETWEEN 61 AND 90 THEN 1 ELSE 0 END), 0) AS three_months_before,
			COALESCE(SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) BETWEEN 31 AND 60 THEN 1 ELSE 0 END), 0) AS two_months_before,
			COALESCE(SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) BETWEEN 1 AND 30 THEN 1 ELSE 0 END), 0) AS one_month_before,
			COALESCE(SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) <= 0 THEN 1 ELSE 0 END), 0) AS expiring_overdue,
			COALESCE(SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) <= 7 THEN 1 ELSE 0 END), 0) AS critical_count,
			COALESCE(SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) BETWEEN 8 AND 30 THEN 1 ELSE 0 END), 0) AS warning_count
		FROM crew_documents
		WHERE status = 'active'
		  AND expiration_date IS NOT NULL
		  AND expiration_date <> '0000-00-00'
	") ?: [];

	$expiryDistribution = [
		'three_months_before' => (int)($expiryRow['three_months_before'] ?? 0),
		'two_months_before' => (int)($expiryRow['two_months_before'] ?? 0),
		'one_month_before' => (int)($expiryRow['one_month_before'] ?? 0),
		'expiring_overdue' => (int)($expiryRow['expiring_overdue'] ?? 0)
	];

	$documentExpirySummary['critical'] = (int)($expiryRow['critical_count'] ?? 0);
	$documentExpirySummary['warning'] = (int)($expiryRow['warning_count'] ?? 0);
	$documentExpirySummary['total_expiring'] =
		$expiryDistribution['one_month_before'] + $expiryDistribution['expiring_overdue'];

	$recentActivities = $db->fetchAll("
		SELECT activity_title, crew_name, activity_time, activity_color
		FROM (
			SELECT
				'Exam Completed' AS activity_title,
				COALESCE(NULLIF(TRIM(CONCAT(cm.first_name, ' ', cm.last_name)), ''), 'Crew Member') AS crew_name,
				ea.end_time AS activity_time,
				'yellow' AS activity_color
			FROM exam_attempts ea
			INNER JOIN crew_master cm ON cm.id = ea.crew_id
			WHERE ea.status = 'completed' AND ea.end_time IS NOT NULL

			UNION ALL

			SELECT
				'Document Uploaded' AS activity_title,
				COALESCE(NULLIF(TRIM(CONCAT(cm.first_name, ' ', cm.last_name)), ''), 'Crew Member') AS crew_name,
				cd.upload_date AS activity_time,
				'blue' AS activity_color
			FROM crew_documents cd
			INNER JOIN crew_master cm ON cm.crew_no = cd.crew_no
			WHERE cd.upload_date IS NOT NULL
		) AS activity_feed
		ORDER BY activity_time DESC
		LIMIT 5
	") ?: [];

	$documentExpiryRowsRaw = $db->fetchAll("
		SELECT
			cd.id,
			cd.crew_id,
			cd.crew_no,
			cd.document_category,
			cd.file_name,
			cd.expiration_date,
			DATEDIFF(cd.expiration_date, CURDATE()) AS days_left,
			COALESCE(NULLIF(TRIM(CONCAT(cm.first_name, ' ', cm.last_name)), ''), cd.crew_no, 'Unknown Crew') AS crew_name,
			COALESCE(NULLIF(TRIM(p.position_name), ''), 'N/A') AS position_name
		FROM crew_documents cd
		LEFT JOIN crew_master cm ON cm.id = cd.crew_id OR cm.crew_no = cd.crew_no
		LEFT JOIN positions p ON p.id = cm.position_id
		WHERE cd.status = 'active'
		  AND cd.expiration_date IS NOT NULL
		  AND cd.expiration_date <> '0000-00-00'
		ORDER BY cd.expiration_date ASC
		LIMIT 50
	") ?: [];

	$peopleBuckets = [
		'critical' => [],
		'warning' => [],
		'normal' => []
	];

	foreach ($documentExpiryRowsRaw as $row) {
		$daysLeft = isset($row['days_left']) ? (int)$row['days_left'] : null;
		if ($daysLeft === null) {
			continue;
		}

		if ($daysLeft <= 7) {
			$statusKey = 'critical';
		} elseif ($daysLeft <= 30) {
			$statusKey = 'warning';
		} else {
			continue; // skip beyond 30 days per requirement
		}

		$crewNoKey = trim((string)($row['crew_no'] ?? ''));
		if ($crewNoKey === '') {
			$crewNoKey = 'crew_id_' . (string)($row['crew_id'] ?? '');
		}
		$peopleBuckets[$statusKey][$crewNoKey] = true;

		$rawCategory = trim((string)($row['document_category'] ?? ''));
		$docLabel = ucwords(str_replace(['_', '-'], ' ', $rawCategory));

		$fileNameForType = strtolower((string)($row['file_name'] ?? ''));
		if ($docLabel === '' || strtolower($docLabel) === 'document') {
			if (strpos($fileNameForType, 'nbi') !== false) {
				$docLabel = 'NBI';
			} elseif (strpos($fileNameForType, 'yellow') !== false && strpos($fileNameForType, 'fever') !== false) {
				$docLabel = 'Yellow Fever';
			} elseif (strpos($fileNameForType, 'passport') !== false) {
				$docLabel = 'Passport';
			} elseif (strpos($fileNameForType, 'seaman') !== false || strpos($fileNameForType, 'seamans') !== false) {
				$docLabel = "Seaman's Book";
			} elseif (strpos($fileNameForType, 'medical') !== false) {
				$docLabel = 'Medical Certificate';
			} elseif (strpos($fileNameForType, 'coc') !== false) {
				$docLabel = 'COC';
			} elseif (strpos($fileNameForType, 'cop') !== false) {
				$docLabel = 'COP';
			}
		}

		if ($docLabel === '' || strtolower($docLabel) === 'document') {
			$docLabel = 'Unknown Document Type';
		}

		$expiryDateFormatted = '';
		if (!empty($row['expiration_date'])) {
			$expiryTimestamp = strtotime((string)$row['expiration_date']);
			$expiryDateFormatted = $expiryTimestamp ? date('m-d-y', $expiryTimestamp) : (string)$row['expiration_date'];
		}

		$daysText = $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's');
		if ($daysLeft < 0) {
			$daysText = abs($daysLeft) . ' day' . (abs($daysLeft) === 1 ? '' : 's') . ' overdue';
		} elseif ($daysLeft === 0) {
			$daysText = 'Today';
		}

		$crewNameRaw = trim((string)($row['crew_name'] ?? 'Unknown Crew'));
		$crewNameDisplay = ucwords(strtolower($crewNameRaw));

		$positionNameRaw = trim((string)($row['position_name'] ?? 'N/A'));
		$positionNameDisplay = ucwords(strtolower($positionNameRaw));

		$documentExpiryRows[] = [
			'crew_name' => $crewNameDisplay,
			'position_name' => $positionNameDisplay,
			'document_label' => $docLabel,
			'expiration_date' => $expiryDateFormatted,
			'days_text' => $daysText,
			'status_key' => $statusKey,
			'status_label' => ucfirst($statusKey)
		];
	}

	$documentExpiryPeopleCounts = [
		'critical' => count($peopleBuckets['critical']),
		'warning' => count($peopleBuckets['warning']),
		'normal' => 0
	];
} catch (Exception $e) {
	// Keep defaults to avoid dashboard crash
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
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
				<h2 class="page-title">DASHBOARD</h2>

				<div class="metrics">
					<div class="metric card">
						<div class="metric__content">
							<div class="metric__label">Total Crew Members</div>
							<div class="metric__number"><?php echo $dashboardStats['total_crew']; ?></div>
							<div class="metric__note">+12 this month</div>
						</div>
						<div class="metric__icon icon-users">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
						</div>
					</div>
					<div class="metric card">
						<div class="metric__content">
							<div class="metric__label">Crew on board</div>
							<div class="metric__number"><?php echo $dashboardStats['on_board']; ?></div>
							<div class="metric__note">+8 this week</div>
						</div>
						<div class="metric__icon icon-ship">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 21c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1 .6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"></path><path d="M19.38 20A11.6 11.6 0 0 0 21 14l-9-4-9 4c0 2.9.94 5.34 2.81 7.76"></path><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v6"></path><path d="M12 10v4"></path><path d="M12 2v3"></path></svg>
						</div>
					</div>
					<div class="metric card">
						<div class="metric__content">
							<div class="metric__label">Active Vessels</div>
							<div class="metric__number"><?php echo $dashboardStats['active_vessels']; ?></div>
							<div class="metric__note">All operational</div>
						</div>
						<div class="metric__icon icon-vessel">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 21c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1 .6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"></path><path d="M19.38 20A11.6 11.6 0 0 0 21 14l-9-4-9 4c0 2.9.94 5.34 2.81 7.76"></path><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v6"></path><path d="M12 10v4"></path><path d="M12 2v3"></path></svg>
						</div>
					</div>
					<div class="metric card">
						<div class="metric__content">
							<div class="metric__label">Documents Expiring</div>
							<div class="metric__number"><?php echo $documentExpirySummary['total_expiring']; ?></div>
							<div class="metric__note"><?php echo $documentExpirySummary['critical']; ?> critical, <?php echo $documentExpirySummary['warning']; ?> warning</div>
						</div>
						<div class="metric__icon icon-alert">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
						</div>
					</div>
				</div>

				<div class="grid">
					<div class="card chart">
						<div class="card__title">Position Overview</div>
						<canvas id="overviewChart" aria-label="Overview chart" role="img"></canvas>
					</div>
					<div class="card chart">
						<div class="card__title">Expiry Distribution</div>
						<canvas id="expiryChart" aria-label="Expiry distribution" role="img"></canvas>
					</div>
				</div>

				<div class="grid">
					<div class="card">
						<div class="card__header">
							<div class="card__title">Upcoming Task</div>
							<button class="card__icon task-add-btn" id="openTaskModal" type="button" title="Add Task">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><line x1="12" y1="14" x2="12" y2="18"></line><line x1="10" y1="16" x2="14" y2="16"></line></svg>
							</button>
						</div>
						<ul class="tasks">
							<?php if (!empty($upcomingTasks)): ?>
								<?php foreach ($upcomingTasks as $task): ?>
									<?php
										$taskTitle = trim((string)($task['title'] ?? 'Untitled Task'));
										$taskDate = trim((string)($task['date'] ?? ''));
										$taskPriority = strtolower(trim((string)($task['priority'] ?? 'medium')));
										$badgeClass = 'badge--medium';
										if ($taskPriority === 'high') $badgeClass = 'badge--high';
										if ($taskPriority === 'low') $badgeClass = 'badge--low';
										$taskDateText = $taskDate !== '' ? date('M d, Y', strtotime($taskDate)) : 'No date';
									?>
									<li class="task">
										<div class="task__content">
											<div><?php echo htmlspecialchars($taskTitle); ?></div>
											<div class="task__date"><?php echo htmlspecialchars($taskDateText); ?></div>
										</div>
										<div class="task__actions">
											<span class="badge <?php echo htmlspecialchars($badgeClass); ?>"><?php echo htmlspecialchars(ucfirst($taskPriority)); ?></span>
											<form method="POST" class="task-delete-form">
												<input type="hidden" name="dashboard_task_action" value="delete">
												<input type="hidden" name="task_id" value="<?php echo htmlspecialchars((string)($task['id'] ?? '')); ?>">
												<button type="submit" class="task-delete-btn" title="Delete task">×</button>
											</form>
										</div>
									</li>
								<?php endforeach; ?>
							<?php else: ?>
								<li class="task">
									<div class="task__content">
										<div>No upcoming task yet.</div>
										<div class="task__date">Click calendar icon to add one.</div>
									</div>
								</li>
							<?php endif; ?>
						</ul>
					</div>
					<div class="card">
						<div class="card__title">Recent Activity</div>
						<ul class="activity">
							<?php if (!empty($recentActivities)): ?>
								<?php foreach ($recentActivities as $activity): ?>
									<?php
										$activityTitle = (string)($activity['activity_title'] ?? 'Activity');
										$activityCrewName = (string)($activity['crew_name'] ?? 'Crew Member');
										$activityTimeRaw = $activity['activity_time'] ?? null;
										$activityColor = strtolower((string)($activity['activity_color'] ?? 'blue'));
										$activityColorClass = $activityColor === 'yellow' ? 'activity__avatar--yellow' : 'activity__avatar--blue';
										$activityTimeText = 'Just now';
										if (!empty($activityTimeRaw)) {
											$timestamp = strtotime((string)$activityTimeRaw);
											if ($timestamp !== false) {
												$secondsAgo = time() - $timestamp;
												if ($secondsAgo < 60) {
													$activityTimeText = 'Just now';
												} elseif ($secondsAgo < 3600) {
													$activityTimeText = floor($secondsAgo / 60) . ' minutes ago';
												} elseif ($secondsAgo < 86400) {
													$activityTimeText = floor($secondsAgo / 3600) . ' hours ago';
												} else {
													$activityTimeText = date('m-d-Y', $timestamp);
												}
											}
										}
									?>
									<li class="activity__item">
										<div class="activity__avatar <?php echo htmlspecialchars($activityColorClass); ?>">
											<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
										</div>
										<div class="activity__content">
											<strong><?php echo htmlspecialchars($activityTitle); ?></strong>
											<div class="activity__meta"><?php echo htmlspecialchars($activityCrewName . ' · ' . $activityTimeText); ?></div>
										</div>
									</li>
								<?php endforeach; ?>
							<?php else: ?>
								<li class="activity__item">
									<div class="activity__avatar activity__avatar--blue">
										<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
									</div>
									<div class="activity__content">
										<strong>No recent activity yet</strong>
										<div class="activity__meta">Activity will appear here automatically.</div>
									</div>
								</li>
							<?php endif; ?>
						</ul>
					</div>
				</div>

				<div class="grid dashboard-extra-row">
					<div class="card calendar-card">
						<div class="mini-calendar">
							<div class="mini-calendar__header">
								<button class="mini-calendar__nav" id="miniCalPrev" type="button">&#10094;</button>
								<span id="miniCalMonthLabel">Month Year</span>
								<button class="mini-calendar__nav" id="miniCalNext" type="button">&#10095;</button>
							</div>
							<div class="mini-calendar__weekdays">
								<span>MON</span><span>TUE</span><span>WED</span><span>THU</span><span>FRI</span><span>SAT</span><span>SUN</span>
							</div>
							<div class="mini-calendar__days" id="miniCalDays"></div>
						</div>
					</div>

					<div class="card events-card">
						<div class="card__title">UPCOMING EVENTS</div>
						<ul class="event-list">
							<li>
								<div>
									<div class="event-title">New Crew Member Added</div>
									<div class="event-meta">12-12-25 &nbsp; • &nbsp; 10:00 AM</div>
								</div>
								<span class="event-badge contact">Contact</span>
							</li>
							<li>
								<div>
									<div class="event-title">Safety Traning Session</div>
									<div class="event-meta">12-15-25 &nbsp; • &nbsp; 8:00 AM</div>
								</div>
								<span class="event-badge training">Training</span>
							</li>
							<li>
								<div>
									<div class="event-title">Certificate Verification</div>
									<div class="event-meta">12-15-25 &nbsp; • &nbsp; 8:00 AM</div>
								</div>
								<span class="event-badge cert">Certification</span>
							</li>
							<li>
								<div>
									<div class="event-title">Client Meeting</div>
									<div class="event-meta">12-18-25 &nbsp; • &nbsp; 3:00 PM</div>
								</div>
								<span class="event-badge meeting">Meeting</span>
							</li>
							<li>
								<div>
									<div class="event-title">Meeting with staff</div>
									<div class="event-meta">12-20-25 &nbsp; • &nbsp; 3:00 PM</div>
								</div>
								<span class="event-badge meeting">Meeting</span>
							</li>
						</ul>
					</div>
				</div>

				<div class="card document-expiry-card">
					<div class="doc-expiry__header">
						<div>
							<div class="doc-expiry__title">Document Expiry Tracking</div>
							<div class="doc-expiry__subtitle">Monitor Crew Document Expirations</div>
						</div>
					</div>

					<div class="doc-expiry__stats">
						<div class="doc-stat doc-stat--critical">
							<div class="doc-stat__title">Critical ( < 7 days )</div>
							<div class="doc-stat__value"><?php echo (int)$documentExpiryPeopleCounts['critical']; ?> People</div>
						</div>
						<div class="doc-stat doc-stat--warning">
							<div class="doc-stat__title">Warning (8-30 days)</div>
							<div class="doc-stat__value"><?php echo (int)$documentExpiryPeopleCounts['warning']; ?> People</div>
						</div>
						<div class="doc-stat doc-stat--normal">
							<div class="doc-stat__title">Normal ( > 30 days)</div>
							<div class="doc-stat__value"><?php echo (int)$documentExpiryPeopleCounts['normal']; ?> People</div>
						</div>
					</div>

					<div class="doc-expiry__list">
						<?php if (!empty($documentExpiryRows)): ?>
							<?php foreach ($documentExpiryRows as $expiryItem): ?>
								<div class="doc-row <?php echo htmlspecialchars(strtolower((string)$expiryItem['status_key'])); ?>">
									<div class="doc-row__top">
										<div class="doc-row__person">
											<strong><?php echo htmlspecialchars((string)$expiryItem['crew_name']); ?></strong>
											<div class="doc-row__rank"><?php echo htmlspecialchars((string)$expiryItem['position_name']); ?></div>
										</div>
										<span class="doc-pill <?php echo htmlspecialchars(strtolower((string)$expiryItem['status_key'])); ?>">
											<?php echo htmlspecialchars((string)$expiryItem['status_label']); ?>
										</span>
									</div>
									<div class="doc-row__meta">
										<span class="doc-col doc-col--document">
											<span class="doc-col__label">Document</span>
											<span class="doc-col__value"><?php echo htmlspecialchars((string)$expiryItem['document_label']); ?></span>
										</span>
										<span class="doc-col doc-col--date">
											<span class="doc-col__label">Expiry Date</span>
											<span class="doc-col__value"><?php echo htmlspecialchars((string)$expiryItem['expiration_date']); ?></span>
										</span>
										<span class="doc-col doc-col--days">
											<span class="doc-col__label">Days Remaining</span>
											<span class="doc-col__value"><?php echo htmlspecialchars((string)$expiryItem['days_text']); ?></span>
										</span>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<div class="doc-row normal">
								<div class="doc-row__top">
									<strong>No expiring documents found</strong>
									<span class="doc-pill normal">Normal</span>
								</div>
								<div class="doc-row__meta">
									<span class="doc-col doc-col--document">
										<span class="doc-col__label">Document</span>
										<span class="doc-col__value">N/A</span>
									</span>
									<span class="doc-col doc-col--date">
										<span class="doc-col__label">Expiry Date</span>
										<span class="doc-col__value">N/A</span>
									</span>
									<span class="doc-col doc-col--days">
										<span class="doc-col__label">Days Remaining</span>
										<span class="doc-col__value">0 day</span>
									</span>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</main>
	</div>

    <?php include '../includes/footer.php'; ?>

	<div class="task-modal" id="taskModal" aria-hidden="true">
		<div class="task-modal__backdrop" id="taskModalBackdrop"></div>
		<div class="task-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="taskModalTitle">
			<div class="task-modal__header">
				<h3 id="taskModalTitle">Add Upcoming Task</h3>
				<button type="button" class="task-modal__close" id="closeTaskModal">×</button>
			</div>
			<form method="POST" class="task-modal__form">
				<input type="hidden" name="dashboard_task_action" value="add">
				<label>
					Task Title
					<input type="text" name="task_title" required maxlength="120" placeholder="e.g. Contract Renewal - Ocean 1">
				</label>
				<label>
					Date
					<input type="date" name="task_date" required>
				</label>
				<label>
					Priority
					<select name="task_priority">
						<option value="high">High</option>
						<option value="medium" selected>Medium</option>
						<option value="low">Low</option>
					</select>
				</label>
				<div class="task-modal__actions">
					<button type="button" class="btn ghost" id="cancelTaskModal">Cancel</button>
					<button type="submit" class="btn primary">Save Task</button>
				</div>
			</form>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script>
		// Overview bar chart — onboard crew count per position
		const ovCtx = document.getElementById('overviewChart').getContext('2d');
		const overviewChartLabels = <?php echo json_encode($overviewChartLabels); ?>;
		const overviewChartDatasets = <?php echo json_encode($overviewChartDatasets); ?>;

		const overviewAllValues = overviewChartDatasets.flatMap(ds => Array.isArray(ds.data) ? ds.data : []);
		const overviewMaxValue = overviewAllValues.length ? Math.max(...overviewAllValues, 0) : 0;
		let overviewTickValues;

		if (overviewMaxValue <= 10) {
			overviewTickValues = [0, 2, 4, 6, 8, 10];
		} else if (overviewMaxValue <= 20) {
			overviewTickValues = [0, 5, 10, 15, 20];
		} else {
			overviewTickValues = [0, 20, 40, 60, 80, 100];
		}
		const overviewAxisMax = overviewTickValues[overviewTickValues.length - 1];
		new Chart(ovCtx, {
			type: 'bar',
			data: {
				labels: overviewChartLabels,
				datasets: overviewChartDatasets
			},
			options: { 
				responsive: true, 
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'bottom',
						labels: { padding: 20, font: { size: 11 } }
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						min: 0,
						max: overviewAxisMax,
						afterBuildTicks: (axis) => {
							axis.ticks = overviewTickValues.map(v => ({ value: v }));
						},
						ticks: {
							precision: 0,
							callback: function(value) {
								return overviewTickValues.includes(value) ? value : '';
							}
						}
					}
				},
				layout: { padding: { top: 25, bottom: 5 } }
			}
		});

		// Expiry pie chart
		const exCtx = document.getElementById('expiryChart').getContext('2d');
		const expiryChartData = <?php echo json_encode([
			$expiryDistribution['three_months_before'],
			$expiryDistribution['two_months_before'],
			$expiryDistribution['one_month_before'],
			$expiryDistribution['expiring_overdue']
		]); ?>;

		new Chart(exCtx, {
			type: 'pie',
			data: {
				labels: ['3 Months Before','2 Months Before','1 Month Before','Expiring/Overdue'],
				datasets: [{ 
					data: expiryChartData, 
					backgroundColor: ['#4CAF50','#2196F3','#FFC107','#F44336']
				}]
			},
			options: { 
				responsive: true, 
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'left',
						labels: { padding: 15, font: { size: 11 } }
					}
				}
			}
		});

		// Real-time mini calendar
		const miniCalMonthLabel = document.getElementById('miniCalMonthLabel');
		const miniCalDays = document.getElementById('miniCalDays');
		const miniCalPrev = document.getElementById('miniCalPrev');
		const miniCalNext = document.getElementById('miniCalNext');

		const monthNames = [
			'January', 'February', 'March', 'April', 'May', 'June',
			'July', 'August', 'September', 'October', 'November', 'December'
		];

		const dashboardTasks = <?php echo json_encode(array_map(function ($task) {
			return [
				'title' => (string)($task['title'] ?? ''),
				'date' => (string)($task['date'] ?? ''),
				'priority' => (string)($task['priority'] ?? 'medium')
			];
		}, $upcomingTasks)); ?>;

		const taskDatesSet = new Set(
			dashboardTasks
				.map(task => (task.date || '').trim())
				.filter(date => /^\d{4}-\d{2}-\d{2}$/.test(date))
		);

		const today = new Date();
		let currentMonthDate = new Date(today.getFullYear(), today.getMonth(), 1);

		function renderMiniCalendar(dateObj) {
			const year = dateObj.getFullYear();
			const month = dateObj.getMonth();

			miniCalMonthLabel.textContent = `${monthNames[month]} ${year}`;
			miniCalDays.innerHTML = '';

			const firstDay = new Date(year, month, 1);
			const lastDay = new Date(year, month + 1, 0);

			// Convert JS Sunday-first to Monday-first index
			const startOffset = (firstDay.getDay() + 6) % 7;
			const daysInMonth = lastDay.getDate();

			for (let i = 0; i < startOffset; i++) {
				const emptyCell = document.createElement('span');
				emptyCell.textContent = '';
				miniCalDays.appendChild(emptyCell);
			}

			for (let d = 1; d <= daysInMonth; d++) {
				const dayCell = document.createElement('span');
				dayCell.textContent = d;

				const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
				if (taskDatesSet.has(dateKey)) {
					dayCell.classList.add('has-task-date');
					dayCell.title = 'Has upcoming task';
				}

				const isToday =
					d === today.getDate() &&
					month === today.getMonth() &&
					year === today.getFullYear();

				if (isToday) {
					dayCell.style.background = '#0f4c81';
					dayCell.style.color = '#fff';
					dayCell.style.borderRadius = '999px';
					dayCell.style.fontWeight = '700';
					dayCell.style.display = 'inline-flex';
					dayCell.style.alignItems = 'center';
					dayCell.style.justifyContent = 'center';
					dayCell.style.width = '32px';
					dayCell.style.height = '32px';
					dayCell.style.margin = '0 auto';
				}

				miniCalDays.appendChild(dayCell);
			}
		}

		miniCalPrev.addEventListener('click', function () {
			currentMonthDate = new Date(currentMonthDate.getFullYear(), currentMonthDate.getMonth() - 1, 1);
			renderMiniCalendar(currentMonthDate);
		});

		miniCalNext.addEventListener('click', function () {
			currentMonthDate = new Date(currentMonthDate.getFullYear(), currentMonthDate.getMonth() + 1, 1);
			renderMiniCalendar(currentMonthDate);
		});

		renderMiniCalendar(currentMonthDate);

		const taskModal = document.getElementById('taskModal');
		const openTaskModalBtn = document.getElementById('openTaskModal');
		const closeTaskModalBtn = document.getElementById('closeTaskModal');
		const cancelTaskModalBtn = document.getElementById('cancelTaskModal');
		const taskModalBackdrop = document.getElementById('taskModalBackdrop');

		function openTaskModal() {
			if (!taskModal) return;
			taskModal.classList.add('is-open');
			taskModal.setAttribute('aria-hidden', 'false');
		}

		function closeTaskModal() {
			if (!taskModal) return;
			taskModal.classList.remove('is-open');
			taskModal.setAttribute('aria-hidden', 'true');
		}

		if (openTaskModalBtn) openTaskModalBtn.addEventListener('click', openTaskModal);
		if (closeTaskModalBtn) closeTaskModalBtn.addEventListener('click', closeTaskModal);
		if (cancelTaskModalBtn) cancelTaskModalBtn.addEventListener('click', closeTaskModal);
		if (taskModalBackdrop) taskModalBackdrop.addEventListener('click', closeTaskModal);

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') closeTaskModal();
		});
	</script>
</body>
</html>
