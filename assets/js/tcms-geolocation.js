/**
 * TCMS Geolocalización JavaScript
 *
 * @package TCMS_Messaging_System
 */

// INICIALIZAR CUANDO EL DOM ESTE LISTO
document.addEventListener('DOMContentLoaded', function() {
    // iniicializando geolocalizacion.
    TCMSGeolocation.init();
});

// Geolocation object
const TCMSGeolocation = {
    /**
     * localizacion de usuario
     */
    userLocation: null,
    
    /**
     * Ubicacion predeterminada si falta la geoloaclización
     */
    defaultLocation: {
        latitude: 40.407115, 
        longitude: -3.693401,
        city: 'Madrid',
        country: 'España'
    },
    
    /**
     * Initializando geolocalización
     */
    init: function() {
        // Check if geolocation is needed on this page
        if (document.querySelector('.tcms-users-nearby, .tcms-saunas-nearby')) {
            this.getUserLocation();
            
            // Initialize location update button
            const updateButton = document.querySelector('.tcms-update-location');
            if (updateButton) {
                updateButton.addEventListener('click', () => {
                    this.getUserLocation(true);
                });
            }
        }
    },
    
    /**
     * Get user location
     */
    getUserLocation: function(forceUpdate = false) {
        // Update UI to show we're detecting location
        this.updateLocationUI('detecting');
        
        // First check if we have a saved location (unless forcing update)
        if (!forceUpdate) {
            this.getSavedLocation()
                .then(location => {
                    if (location) {
                        this.userLocation = location;
                        this.updateLocationUI('found', location.city || 'Location found');
                        
                        // Trigger location update event
                        this.triggerLocationUpdate();
                    } else {
                        // No saved location, get current position
                        this.getCurrentPosition();
                    }
                })
                .catch(() => {
                    // Error getting saved location, try current position
                    this.getCurrentPosition();
                });
        } else {
            // Force update, get current position
            this.getCurrentPosition();
        }
    },
    
    /**
     * Get saved location from server
     */
    getSavedLocation: function() {
        return new Promise((resolve, reject) => {
            if (typeof tcms_ajax === 'undefined') {
                reject('AJAX object not available');
                return;
            }
            
            fetch(tcms_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'tcms_get_location',
                    nonce: tcms_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.location) {
                    resolve(data.data.location);
                } else {
                    resolve(null);
                }
            })
            .catch(error => {
                console.error('Error getting saved location:', error);
                reject(error);
            });
        });
    },
    
    /**
     * Get current position using browser geolocation
     */
    getCurrentPosition: function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                // Success
                position => {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    
                    // Reverse geocode to get city/country
                    this.reverseGeocode(latitude, longitude)
                        .then(locationInfo => {
                            this.userLocation = {
                                latitude: latitude,
                                longitude: longitude,
                                city: locationInfo.city || '',
                                country: locationInfo.country || ''
                            };
                            
                            // Save location
                            this.saveUserLocation();
                            
                            // Update UI
                            this.updateLocationUI('found', locationInfo.city || 'Location found');
                            
                            // Trigger location update event
                            this.triggerLocationUpdate();
                        })
                        .catch(() => {
                            // Error with reverse geocoding, but we still have coords
                            this.userLocation = {
                                latitude: latitude,
                                longitude: longitude
                            };
                            
                            // Save location
                            this.saveUserLocation();
                            
                            // Update UI
                            this.updateLocationUI('found', 'Location found');
                            
                            // Trigger location update event
                            this.triggerLocationUpdate();
                        });
                },
                // Error
                error => {
                    console.error('Geolocation error:', error);
                    
                    // Use default location
                    this.userLocation = this.defaultLocation;
                    
                    // Update UI
                    this.updateLocationUI('error', 'Location error, using default');
                    
                    // Trigger location update event
                    this.triggerLocationUpdate();
                },
                // Options
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 600000 // 10 minutes
                }
            );
        } else {
            console.error('Geolocation not supported');
            
            // Use default location
            this.userLocation = this.defaultLocation;
            
            // Update UI
            this.updateLocationUI('error', 'Geolocation not supported');
            
            // Trigger location update event
            this.triggerLocationUpdate();
        }
    },
    
    /**
     * Reverse geocode coordinates to get city/country
     */
    reverseGeocode: function(latitude, longitude) {
        return new Promise((resolve, reject) => {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.address) {
                        const city = data.address.city || data.address.town || data.address.village || '';
                        const country = data.address.country || '';
                        
                        resolve({
                            city: city,
                            country: country,
                            address: data.address
                        });
                    } else {
                        reject('No address data found');
                    }
                })
                .catch(error => {
                    console.error('Error reverse geocoding:', error);
                    reject(error);
                });
        });
    },
    
    /**
     * Save user location to server
     */
    saveUserLocation: function() {
        if (typeof tcms_ajax === 'undefined' || !this.userLocation) {
            return;
        }
        
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_update_location',
                nonce: tcms_ajax.nonce,
                latitude: this.userLocation.latitude,
                longitude: this.userLocation.longitude,
                city: this.userLocation.city || '',
                country: this.userLocation.country || '',
                privacy_level: 2 // Default to members only
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error saving location:', data.data.message);
            }
        })
        .catch(error => {
            console.error('Error saving location:', error);
        });
    },
    
    /**
     * Update location UI elements
     */
    updateLocationUI: function(status, message = '') {
        const locationElement = document.querySelector('.tcms-current-location');
        
        if (!locationElement) {
            return;
        }
        
        switch (status) {
            case 'detecting':
                locationElement.textContent = 'Detecting location...';
                break;
            case 'found':
                locationElement.textContent = message;
                break;
            case 'error':
                locationElement.textContent = message;
                break;
        }
    },
    
    /**
     * Trigger location update event
     */
    triggerLocationUpdate: function() {
        // Create custom event
        const event = new CustomEvent('tcms:locationUpdated', {
            detail: {
                location: this.userLocation
            }
        });
        
        // Dispatch event
        document.dispatchEvent(event);
    },
    
    /**
     * Calculate distance between two coordinates
     */
    calculateDistance: function(lat1, lon1, lat2, lon2) {
        if (!lat1 || !lon1 || !lat2 || !lon2) {
            return null;
        }
        
        const R = 6371; // Radius of the earth in km
        const dLat = this.deg2rad(lat2 - lat1);
        const dLon = this.deg2rad(lon2 - lon1);
        
        const a = 
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) * 
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
            
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const distance = R * c; // Distance in km
        
        return distance;
    },
    
    /**
     * Convert degrees to radians
     */
    deg2rad: function(deg) {
        return deg * (Math.PI / 180);
    }
};
