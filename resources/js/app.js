// import './bootstrap';
import * as te from 'tw-elements';
import Alpine from 'alpinejs';
import originFilter from './origin-filter';

window.Alpine = Alpine;

Alpine.data('originFilter', originFilter);

Alpine.start();
