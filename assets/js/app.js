import { Application, Controller } from '@hotwired/stimulus';
import { createApp } from 'vue'
import App from './components/App.vue';
import '../css/styles.scss';

const application = Application.start();
application.debug = process.env.NODE_ENV === 'development';
application.register(
    'terminal42--dam-integrator',
    class extends Controller {
        connect() {
            this.app = createApp(App, JSON.parse(this.element.dataset.config));
            this.app.mount(this.element)
        }

        disconnect() {
            if (this.app) {
                this.app.unmount()
            }
        }
    },
);
