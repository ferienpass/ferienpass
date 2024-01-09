'use strict';

import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {Chart} from 'frappe-charts';

export default class extends Controller {
    static values = {
        labels: Array,
        datasets: Array,
    };

    declare readonly labelsValue?: string[];
    declare readonly datasetsValue?: number[];

    connect() {
        new Chart(this.element, {
            title: "Anmeldungen je Status",
            data: {
                labels: this.labelsValue,
                datasets: [
                    {
                        name: "Anmeldungen",
                        values: this.datasetsValue
                    }
                ]
            },
            colors: ['#74B739', '#EDC43B', '#C9473D', '#cccccc', '#dddddd'],
            height: 180,
            type: 'percentage',
        });
    }
}
