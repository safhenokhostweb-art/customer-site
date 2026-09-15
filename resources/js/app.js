import './bootstrap';
import toPersianNumbers from './helpers';
import Alpine from 'alpinejs';
import supportChat from './components/support-chat';
import mediaLibrary from './components/media-library';

// Alpine.js setup
window.Alpine = Alpine;

// Register components
Alpine.data('supportChat', supportChat);
Alpine.data('mediaLibrary', mediaLibrary);

Alpine.start();

// در دسترس قرار دادن تابع برای استفاده در inline scripts
window.toPersianNumbers = toPersianNumbers;
