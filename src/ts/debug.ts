'use strict';

import { on } from './modules/utils.ts';

on(document, 'DOMContentLoaded', () => {
    const message = 'Debugging active!';
    console.log(message);
});
