'use strict';
import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {Chart} from 'frappe-charts';

export default class default_1 extends Controller {
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
default_1.values = {
    labels: Array,
    datasets: Array,
};
