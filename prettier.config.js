module.exports = {
    printWidth: 80,
    tabWidth: 4,
    useTabs: false,
    semi: true,
    singleQuote: true,
    overrides: [
        {
            files: '*.yml',
            options: {
                useTabs: false,
                tabWidth: 2,
            },
        },
    ],
};
