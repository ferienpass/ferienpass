import {controller, target, attr} from '@github/catalyst'
// @ts-ignore
import Litepicker from 'litepicker/dist/nocss/litepicker.umd.js';

require('./litepicker.css')

@controller
class OfferDatepickerElement extends HTMLElement { // eslint-disable-line no-unused-vars
    picker: Litepicker;

    @target begin: HTMLInputElement
    @target end: HTMLInputElement

    @attr minDate: string = ''
    @attr maxDate: string = ''

    connectedCallback() {
        this.picker = new Litepicker({
            element: this.begin,
            elementEnd: this.end,
            minDate: '' !== this.minDate ? this.minDate : null,
            maxDate: '' !== this.maxDate ? this.maxDate : null,
            inlineMode: true,
            format: 'DD.MM.YYYY',
            autoRefresh: true,
            lang: 'de',
            numberOfColumns: 2,
            numberOfMonths: 2,
            showWeekNumbers: true,
            selectForward: true,
            tooltipText: {one: "Tag (klicken zum Bestätigen)", other: "Tage (klicken zum Bestätigen)"},
            singleMode: false,
            splitView: false,
            setup: (picker: any) => {
                picker.on('before:show', () => {
                    this.begin.hidden = true;
                    this.end.hidden = true;
                });
            },
        });
    }
}
