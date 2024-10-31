import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.start();

import '../../vendor/filament/filament/resources/js/filament.js';
import '../../vendor/filament/widgets/resources/js/widgets.js';
