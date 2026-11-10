import Alpine from 'alpinejs';
import { customerPicker } from './customer-picker.js';

Alpine.data('customerPicker', customerPicker);

window.Alpine = Alpine;

Alpine.start();
