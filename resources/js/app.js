import './bootstrap';
import Alpine from 'alpinejs';
import morph from '@alpinejs/morph';

Alpine.plugin(morph);

window.Alpine = Alpine;
Alpine.start();