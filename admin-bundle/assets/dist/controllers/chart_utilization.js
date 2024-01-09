'use strict';
import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {Chart} from 'frappe-charts';

export default class default_1 extends Controller {
    connect() {
        new Chart(this.element, {
            title: "Auslastung der Angebote",
            data: {
                labels: this.labelsValue,
                datasets: this.datasetsValue,
                yRegions: [
                    {
                        label: "Optimale Auslastung",
                        start: 0.8,
                        end: 1.1,
                        options: { labelPos: 'right' }
                    }
                ],
            },
            barOptions: {
                stacked: 1
            },
            tooltipOptions: {
            //formatTooltipY: d => (d * 100).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' %',
            },
            colors: ['#74B739', '#EDC43B', '#C9473D', '#cccccc'],
            height: 400,
            isNavigable: false,
            type: 'bar',
        });
    }
}
default_1.values = {
    labels: Array,
    datasets: Array,
};
