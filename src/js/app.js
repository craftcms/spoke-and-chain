// Import styles
import( /* webpackChunkName: "styles" */ '../css/main.css');

import "core-js/stable";
import 'alpinejs';
import './components/addresses';
import './components/filter';
import './components/header';
import './components/modal';
import './components/notifications';
import './components/productNav';
import './components/quickReplace';
import './components/sliders';

console.log('Loaded.');

// Init sliders
window.sliders()