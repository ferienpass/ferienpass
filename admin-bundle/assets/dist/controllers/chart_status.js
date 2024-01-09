'use strict';
import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {Chart} from 'frappe-charts';

export default class default_1 extends Controller {
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
default_1.values = {
    labels: Array,
    datasets: Array,
};
