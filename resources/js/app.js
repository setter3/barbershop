import Alpine from 'alpinejs';
import bookingWizard from './booking-wizard';

window.Alpine = Alpine;

Alpine.data('bookingWizard', bookingWizard);
Alpine.start();
