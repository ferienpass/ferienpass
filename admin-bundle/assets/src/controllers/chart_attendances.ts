'use strict';

import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {Chart} from 'frappe-charts';

export default class extends Controller {
    static values = {
        datasets: Object,
    };

    declare readonly datasetsValue?: object;

    connect() {
        new Chart(this.element, {
            title: "Anmeldungen pro Tag",
            type: 'heatmap',
            colors: ["#ebedf0", "#c0ddf9", "#73b3f3", "#3886e1", "#17459e"],
            data: {
                dataPoints: this.datasetsValue,
                // start: this.startValue,
                // end: new Date()
            },
        });
    }
}
