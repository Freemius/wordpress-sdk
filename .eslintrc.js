module.exports = {
    env: {
        browser: true,
        commonjs: true,
        es6: true,
        node: true,
    },
    extends: [
        'eslint:recommended',
        'prettier', // Make sure this is at last.
    ],

    rules: {
        // other rules
        'no-nested-ternary': 'off',
        eqeqeq: ['error', 'smart'],
        'no-console': 'warn',
        'prefer-destructuring': 'warn',
    },
};
