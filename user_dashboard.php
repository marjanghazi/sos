<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guarding Revenue Dashboard</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Load Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <style>
        /* Custom Styles for exact gradient and layout matching */
        :root {
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fc; /* Light background matching the image */
        }

        /* Sidebar Gradient (Deep Blue/Teal) */
        .sidebar-gradient {
            background: linear-gradient(180deg, #0D47A1 0%, #00BCD4 100%);
            background: linear-gradient(135deg, #0e2b4f 0%, #11687a 100%);
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
        }

        /* Metric Card Gradient (Light Blue/Cyan) */
        .card-gradient-1 {
            background: linear-gradient(90deg, #4FC3F7 0%, #B3E5FC 100%);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .card-gradient-2 {
            background: linear-gradient(90deg, #4FC3F7 0%, #81D4FA 100%);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .card-gradient-3 {
             background: linear-gradient(90deg, #4FC3F7 0%, #00BCD4 100%);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .card-gradient-4 {
            background: linear-gradient(90deg, #4FC3F7 0%, #4DD0E1 100%);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Main Content Margin */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .sidebar-gradient {
                width: 100%;
                height: 60px; /* Collapse sidebar into a top bar for mobile */
                position: relative;
            }
            .main-content {
                margin-left: 0;
                padding-top: 0;
            }
        }
    </style>
</head>
<body class="overflow-x-hidden">

    <!-- 1. Sidebar/Navigation -->
    <div class="sidebar-gradient hidden lg:block">
        <div class="p-6 text-white text-xl font-semibold border-b border-white/20 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            Dashboard
        </div>
        <nav class="mt-4">
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150 font-medium bg-white/20">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </div>
            </a>
            <p class="uppercase text-xs text-white/50 tracking-wider py-3 px-6 mt-4">Reports</p>
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150">
                REPORTS
            </a>
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150 flex justify-between items-center">
                Configuration
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150">
                REPORTS
            </a>
            <a href="#" class="block py-3 px-6 text-white/80 hover:bg-white/10 transition duration-150 flex justify-between items-center">
                Reports
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content min-h-screen">

        <!-- Header/Top Bar (Visible on all sizes, but serves as the only header for mobile) -->
        <header class="flex justify-end items-center py-4 px-4 bg-white shadow-sm lg:shadow-none lg:bg-transparent -mt-6 rounded-lg">
            <div class="flex items-center space-x-4">
                <!-- Date Picker/Dropdown -->
                <div class="relative">
                    <select class="appearance-none bg-white border border-gray-300 rounded-lg py-2 pl-3 pr-10 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option>Oct 2025</option>
                        <option>Nov 2025</option>
                        <option>Dec 2025</option>
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <!-- Profile Icon -->
                <div class="w-8 h-8 rounded-full bg-blue-400 overflow-hidden border-2 border-white shadow-md">
                    <!-- Placeholder for profile image -->
                    <img src="https://placehold.co/32x32/77A9FF/FFFFFF?text=P" alt="Profile" class="w-full h-full object-cover">
                </div>
            </div>
        </header>

        <!-- Main Dashboard Body -->
        <main class="mt-8">
            <div class="flex justify-between items-center mb-6 flex-wrap">
                <h1 class="text-2xl font-bold text-gray-800">Guarding Revenue Dashboard</h1>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition duration-200 mt-4 lg:mt-0">
                    Send Reminder Message
                </button>
            </div>

            <!-- 2. Key Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <!-- Card 1: GUARDS STRENGTH -->
                <div class="card-gradient-1 p-6 rounded-xl text-white">
                    <p class="text-sm uppercase font-light opacity-80">GUARDS STRENGTH</p>
                    <h2 class="text-4xl font-extrabold mt-2">10,418</h2>
                </div>

                <!-- Card 2: CLIENTS -->
                <div class="card-gradient-2 p-6 rounded-xl text-white">
                    <p class="text-sm uppercase font-light opacity-80">CLIENTS</p>
                    <h2 class="text-4xl font-extrabold mt-2">726</h2>
                </div>

                <!-- Card 3: TOTAL REVENUE -->
                <div class="card-gradient-3 p-6 rounded-xl text-white">
                    <p class="text-sm uppercase font-light opacity-80">TOTAL REVENUE</p>
                    <h2 class="text-4xl font-extrabold mt-2 tracking-tight">427,177,839 PKR</h2>
                </div>

                <!-- Card 4: REVENUE PER GUARD -->
                <div class="card-gradient-4 p-6 rounded-xl text-white">
                    <p class="text-sm uppercase font-light opacity-80">REVENUE PER GUARD</p>
                    <h2 class="text-4xl font-extrabold mt-2 tracking-tight">41,003.82 PKR</h2>
                </div>

            </div>

            <!-- 3. Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Bar Chart: Top 10 Highest Strength per Client (2/3 width) -->
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Top 10 Highest Strength per Client</h3>
                    <div class="h-96 w-full">
                        <canvas id="barChart"></canvas>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">CanvasJS Trial</p>
                </div>

                <!-- Doughnut Chart: Invoice Type Guard Strength (1/3 width) -->
                <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-md flex flex-col justify-between">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800 text-center">Invoice Type Guard Strength</h3>
                    <div class="relative h-64 w-full flex items-center justify-center">
                        <canvas id="doughnutChart"></canvas>
                        <!-- Center text for the doughnut chart -->
                        <div id="doughnutCenterText" class="absolute text-center">
                            <!-- JS will update this -->
                        </div>
                    </div>
                    <!-- Legend below the chart -->
                    <div class="mt-4 text-center">
                        <p class="text-sm font-medium text-gray-700">Normal Invoice - <span class="text-red-600">9,230</span> Guards</p>
                        <p class="text-sm font-medium text-gray-700">Additional Guards - <span class="text-blue-600">1,188</span> Guards</p>
                    </div>
                     <p class="text-xs text-gray-500 mt-2">CanvasJS Trial</p>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Function to configure and render charts
            function renderCharts() {
                // --- 1. Bar Chart Data and Configuration ---
                const barCtx = document.getElementById('barChart').getContext('2d');
                new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: ['NSC', 'GC', 'MCB', 'UMF-BL', 'RSB', 'PBL', 'NBP', 'Faysal', 'ABL', 'HBL'],
                        datasets: [{
                            label: 'Strength Per Client',
                            data: [950, 810, 690, 650, 630, 580, 520, 480, 450, 410], // Mock data mimicking the chart height in the image
                            backgroundColor: [
                                '#4CAF50', // Green for NSC
                                '#FF9800', // Orange for GC
                                '#03A9F4', // Blue for MCB
                                '#673AB7', // Purple
                                '#F44336', // Red
                                '#E91E63', // Pink
                                '#FFC107', // Amber
                                '#8BC34A', // Light Green
                                '#009688', // Teal
                                '#9E9E9E'  // Grey
                            ],
                            borderColor: 'transparent',
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Allows the chart to fill the container height
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                min: 600, // Setting min value to 600 to visually match the image scale
                                max: 1000,
                                ticks: {
                                    stepSize: 100,
                                    font: { size: 12 }
                                },
                                title: {
                                    display: true,
                                    text: 'Strength Per Client',
                                    font: { size: 14, weight: 'bold' }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: { size: 12 }
                                }
                            }
                        }
                    }
                });

                // --- 2. Doughnut Chart Data and Configuration ---
                const doughnutCtx = document.getElementById('doughnutChart').getContext('2d');
                const totalGuards = 10418;
                const normalInvoice = 9230;
                const additionalGuards = 1188;

                new Chart(doughnutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Normal Invoice', 'Additional Guards'],
                        datasets: [{
                            data: [normalInvoice, additionalGuards],
                            backgroundColor: [
                                '#D32F2F', // Reddish-Brown
                                '#42A5F5' // Blue
                            ],
                            hoverOffset: 4,
                            borderWidth: 0 // Remove white border around slices
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%', // Make it a doughnut (hole size)
                        plugins: {
                            legend: {
                                display: false // Legend is handled manually below the chart
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.parsed + ' Guards (' + ((context.parsed / totalGuards) * 100).toFixed(1) + '%)';
                                        return label;
                                    }
                                }
                            }
                        },
                        layout: {
                            padding: 10
                        }
                    }
                });
            }

            renderCharts();
        });
    </script>

</body>
</html>