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
            title: "Anmeldungen nach Alter",
            data: {
                labels: this.labelsValue,
                datasets: [
                    {
                        name: "Some Data",
                        values: this.datasetsValue
                    }
                ]
            },
            colors: ['light-blue', '#5bc0be', 'blue', 'violet', 'red', 'orange', '#e59f71', 'yellow', 'green', 'light-green', 'purple', 'magenta', '#a69888', 'light-grey', 'dark-grey'],
            height: 180,
            type: 'percentage',
        });
    }
}
