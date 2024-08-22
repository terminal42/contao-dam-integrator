import { createApp } from 'vue'
import App from './components/App.vue';
import '../css/styles.scss';

window.initDamInterface = function (ref, props) {
    createApp(App, props).mount(ref);
};