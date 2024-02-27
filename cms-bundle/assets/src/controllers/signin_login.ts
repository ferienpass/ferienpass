'use strict';

import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {Component, getComponent} from '@symfony/ux-live-component';

export default class extends Controller {
    static values = {
        requestToken: String,
        targetPath: String,
    };

    static targets = ["error", "username", "password", "remember_me"]

    declare readonly targetPathValue?: string;
    declare readonly requestTokenValue?: string;
    declare readonly errorTarget: HTMLElement;
    declare readonly usernameTarget: HTMLInputElement;
    declare readonly passwordTarget: HTMLInputElement;

    declare component?: Component;

    async initialize() {
        if (!(this.element instanceof HTMLElement)) {
            return;
        }

        this.component = await getComponent(this.element);

        this.component.on('render:finished', (component: Component) => {

        });
    }

    async submit(event: SubmitEvent) {
        const response = await fetch('/check_login', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({
                username: this.usernameTarget.value,
                password: this.passwordTarget.value,
                REQUEST_TOKEN: this.requestTokenValue
            })
        })

        const json = await response.json()

        if (!("user" in json)) {
            this.errorTarget.classList.remove('hidden')
        } else {
            this.errorTarget.classList.add('hidden')
            if (this.targetPathValue) {
                window.location.href = this.targetPathValue
            }
        }
    }
}
