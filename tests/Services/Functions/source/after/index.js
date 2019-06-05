'use strict';
exports.main = (event, context, callback) => {
    callback(null, {a: 1, b: 2, c: 3, env: process.env});
};
