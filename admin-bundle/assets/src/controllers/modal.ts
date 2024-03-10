'use strict';

import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {enter, leave} from "el-transition";
//import {Component, getComponent} from '@symfony/ux-live-component';

export default class extends Controller {
    static values = {
    };

    static targets = ["backdrop", "modal"];

    declare readonly backdropTarget: HTMLElement;
    declare readonly modalTarget: HTMLElement;

    show() {
        this.element.classList.remove("hidden");
        enter(this.backdropTarget);
        enter(this.modalTarget);
    }

    hide() {
        Promise.all([
            leave(this.backdropTarget),
            leave(this.modalTarget),
        ]).then(() => {
            this.element.classList.add("hidden");
        });
    }
}
