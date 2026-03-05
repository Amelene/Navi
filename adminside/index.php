<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
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
							<div class="metric__number">300</div>
							<div class="metric__note">+12 this month</div>
						</div>
						<div class="metric__icon icon-users">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
						</div>
					</div>
					<div class="metric card">
						<div class="metric__content">
							<div class="metric__label">Crew on board</div>
							<div class="metric__number">50</div>
							<div class="metric__note">+8 this week</div>
						</div>
						<div class="metric__icon icon-ship">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 21c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1 .6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"></path><path d="M19.38 20A11.6 11.6 0 0 0 21 14l-9-4-9 4c0 2.9.94 5.34 2.81 7.76"></path><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v6"></path><path d="M12 10v4"></path><path d="M12 2v3"></path></svg>
						</div>
					</div>
					<div class="metric card">
						<div class="metric__content">
							<div class="metric__label">Active Vessels</div>
							<div class="metric__number">6</div>
							<div class="metric__note">All operational</div>
						</div>
						<div class="metric__icon icon-vessel">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 21c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1 .6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"></path><path d="M19.38 20A11.6 11.6 0 0 0 21 14l-9-4-9 4c0 2.9.94 5.34 2.81 7.76"></path><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v6"></path><path d="M12 10v4"></path><path d="M12 2v3"></path></svg>
						</div>
					</div>
					<div class="metric card">
						<div class="metric__content">
							<div class="metric__label">Documents Expiring</div>
							<div class="metric__number">2</div>
							<div class="metric__note">1 critical, 1 warning</div>
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
							<div class="card__icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
							</div>
						</div>
						<ul class="tasks">
							<li class="task">
								<div>Contract Renewal - Ocean 1</div>
								<span class="badge badge--high">High</span>
							</li>
							<li class="task">
								<div>Safety Training for New Crew</div>
								<span class="badge badge--medium">Medium</span>
							</li>
						</ul>
					</div>
					<div class="card">
						<div class="card__title">Recent Activity</div>
						<ul class="activity">
							<li class="activity__item">
								<div class="activity__avatar activity__avatar--blue">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
								</div>
								<div class="activity__content">
									<strong>New Crew Member Added</strong>
									<div class="activity__meta">Lee Tan · 15 minutes ago · 12-12-2025</div>
								</div>
							</li>
							<li class="activity__item">
								<div class="activity__avatar activity__avatar--yellow">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
								</div>
								<div class="activity__content">
									<strong>Exam Completed with 95% Score</strong>
									<div class="activity__meta">Amelene Cabatuanod · 1 hour ago</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</main>
	</div>

    <?php include '../includes/footer.php'; ?>

	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script>
		// Overview bar chart — only 2025 populated; total 300 across datasets
		const ovCtx = document.getElementById('overviewChart').getContext('2d');
		new Chart(ovCtx, {
			type: 'bar',
			data: {
				labels: ['2025','2026','2027','2028','2029'],
				datasets: [
					{ label: 'Position 1', data: [100,0,0,0,0], backgroundColor: '#8979FF' },
					{ label: 'Position 2', data: [80,0,0,0,0], backgroundColor: '#FF928A' },
					{ label: 'Position 3', data: [60,0,0,0,0], backgroundColor: '#3CC3DF' },
					{ label: 'Position 4', data: [40,0,0,0,0], backgroundColor: '#FFAE4C' },
					{ label: 'Position 5', data: [20,0,0,0,0], backgroundColor: '#537FF1' }
				]
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
				layout: { padding: { top: 25, bottom: 5 } }
			}
		});

		// Expiry pie chart
		const exCtx = document.getElementById('expiryChart').getContext('2d');
		new Chart(exCtx, {
			type: 'pie',
			data: {
				labels: ['3 Months Before','2 Months Before','1 Month Before','Expiring/Overdue'],
				datasets: [{ 
					data: [45, 25, 20, 10], 
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
	</script>
</body>
</html>
