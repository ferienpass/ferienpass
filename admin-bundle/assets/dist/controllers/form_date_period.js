'use strict';
import {Controller} from '@hotwired/stimulus';
import {easepick, RangePlugin} from '@easepick/bundle';

export default class default_1 extends Controller {
    connect() {
        const begin = this.beginTarget;
        const end = this.endTarget;
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
            zIndex: 20,
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
default_1.values = {
    minDate: String,
    maxDate: String
};
default_1.targets = ["begin", "end"];
