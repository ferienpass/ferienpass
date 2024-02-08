'use strict';

import {Controller} from '@hotwired/stimulus';
import {easepick, RangePlugin} from '@easepick/bundle';

export default class extends Controller {

    static values = {
        minDate: String,
        maxDate: String
    }

    static targets = [ "begin", "end" ]

    declare readonly beginTarget: HTMLElement;
    declare readonly endTarget: HTMLElement;
    declare readonly minDateValue: string;
    declare readonly maxDateValue: string;

    connect() {
        const begin = this.beginTarget
        const end = this.endTarget

        new easepick.create({
            plugins: [RangePlugin],
            element: begin,
            css: [
                "https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css"
            ],
            RangePlugin: {
                elementEnd: end,
                tooltip: true,
                locale: {
                    one: "Tag (klicken zum Bestätigen)",
                    other: "Tage (klicken zum Bestätigen)"
                },
                //startDate: '' !== this.minDateValue ? this.minDateValue : '',
                //endDate: '' !== this.maxDateValue ? this.maxDateValue : '',
            },
            format: 'DD.MM.YYYY',
            lang: 'de',
            grid: 2,
            zIndex:20,
            calendars: 2,
            setup(picker) {
                picker.on('render', (e) => {
                    //begin.style.display = 'none'
                    //end.style.display = 'none'
                });
            },
        });
    }
}
