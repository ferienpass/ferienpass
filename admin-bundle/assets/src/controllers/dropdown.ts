'use strict';

import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {enter, leave} from "el-transition";

export default class extends Controller {
    static targets = ["dropdown"];

    declare readonly dropdownTarget: HTMLElement;

    open() {
        this.dropdownTarget.classList.remove("hidden");
        enter(this.dropdownTarget);
    }

    close() {
        Promise.all([
            leave(this.dropdownTarget),
        ]).then(() => {
            this.dropdownTarget.classList.add("hidden");
        });
    }

    toggle() {
        this.dropdownTarget.classList.contains('hidden') ? this.open() : this.close();
    }
}
