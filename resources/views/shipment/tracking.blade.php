<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Your Shipment</title>
    
    <!-- Include Tailwind CSS or your preferred CSS framework -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">Track Your Shipment</h1>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow">
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <div class="bg-white rounded-lg shadow p-6">
                        <!-- Tracking Form -->
                        <div class="mb-8">
                            <form id="trackingForm" class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-grow">
                                    <label for="trackingNumber" class="block text-sm font-medium text-gray-700 mb-1">
                                        Enter Tracking Number
                                    </label>
                                    <input 
                                        type="text" 
                                        id="trackingNumber" 
                                        name="tracking_number"
                                        placeholder="Enter your tracking number"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                                <div class="flex items-end">
                                    <button 
                                        type="submit" 
                                        id="trackButton"
                                        class="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    >
                                        Track Shipment
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Loading Indicator -->
                        <div id="loadingIndicator" class="hidden flex justify-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                        </div>

                        <!-- Error Message -->
                        <div id="errorMessage" class="hidden mb-6 p-4 bg-red-50 text-red-700 rounded-lg"></div>

                        <!-- Shipment Information -->
                        <div id="shipmentInfo" class="hidden mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500">Tracking Number</p>
                                    <p id="trackingNumberDisplay" class="font-semibold"></p>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500">Carrier</p>
                                    <p id="carrierDisplay" class="font-semibold"></p>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500">Status</p>
                                    <p id="statusDisplay" class="font-semibold"></p>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500">Order ID</p>
                                    <p id="orderDisplay" class="font-semibold"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div id="timelineSection" class="hidden">
                            <h2 class="text-xl font-semibold mb-4">Shipment Timeline</h2>
                            <div id="timeline" class="space-y-4">
                                <!-- Timeline items will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-gray-500 text-sm">
                    &copy; {{ date('Y') }} ECSite. All rights reserved.
                </p>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('trackingForm');
            const trackButton = document.getElementById('trackButton');
            const trackingInput = document.getElementById('trackingNumber');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const errorMessage = document.getElementById('errorMessage');
            const shipmentInfo = document.getElementById('shipmentInfo');
            const timelineSection = document.getElementById('timelineSection');
            const timelineContainer = document.getElementById('timeline');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const trackingNumber = trackingInput.value.trim();
                
                if (!trackingNumber) {
                    showError('Please enter a tracking number');
                    return;
                }
                
                // Show loading, hide previous results
                showLoading();
                
                try {
                    const response = await fetch(`/api/v1/shipment/track?tracking_number=${encodeURIComponent(trackingNumber)}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        displayShipmentInfo(data.data.shipment);
                        displayTimeline(data.data.timeline);
                        hideError();
                    } else {
                        showError(data.message || 'Shipment not found');
                    }
                } catch (error) {
                    console.error('Error tracking shipment:', error);
                    showError('An error occurred while tracking your shipment. Please try again.');
                } finally {
                    hideLoading();
                }
            });
            
            function showLoading() {
                loadingIndicator.classList.remove('hidden');
                trackButton.disabled = true;
                trackButton.textContent = 'Tracking...';
            }
            
            function hideLoading() {
                loadingIndicator.classList.add('hidden');
                trackButton.disabled = false;
                trackButton.textContent = 'Track Shipment';
            }
            
            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.classList.remove('hidden');
            }
            
            function hideError() {
                errorMessage.classList.add('hidden');
            }
            
            function displayShipmentInfo(shipment) {
                document.getElementById('trackingNumberDisplay').textContent = shipment.tracking_number;
                document.getElementById('carrierDisplay').textContent = shipment.carrier || 'Unknown';
                document.getElementById('statusDisplay').textContent = shipment.shipment_status || shipment.status;
                document.getElementById('orderDisplay').textContent = `#${shipment.order_id}`;
                
                shipmentInfo.classList.remove('hidden');
            }
            
            function displayTimeline(timeline) {
                if (timeline.length === 0) {
                    timelineContainer.innerHTML = '<p class="text-gray-500">No tracking information available yet.</p>';
                    timelineSection.classList.remove('hidden');
                    return;
                }
                
                timelineContainer.innerHTML = timeline.map((event, index) => {
                    const eventDate = new Date(event.event_time).toLocaleDateString();
                    const eventTime = new Date(event.event_time).toLocaleTimeString();
                    
                    return `
                        <div class="flex ${index !== timeline.length - 1 ? 'pb-6' : ''}">
                            <div class="flex flex-col items-center mr-4">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                ${index !== timeline.length - 1 ? '<div class="w-0.5 h-full bg-blue-200 mt-1"></div>' : ''}
                            </div>
                            <div class="pb-2">
                                <p class="font-semibold">${event.status}</p>
                                <p class="text-gray-600">${event.description}</p>
                                <p class="text-sm text-gray-500">${eventDate} at ${eventTime}</p>
                                ${event.location ? `<p class="text-sm text-gray-500">Location: ${event.location}</p>` : ''}
                            </div>
                        </div>
                    `;
                }).join('');
                
                timelineSection.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>