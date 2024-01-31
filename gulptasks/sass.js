const {getSCSSCompiler} = require('./compilers');

const scssSourceDir = 'assets/scss';

const scssSources = [
    `${scssSourceDir}/**/*.scss`,
];

const cssOutPut = 'assets/css';

exports.getSdkScssCompiler = (isProd) => getSCSSCompiler(scssSources, cssOutPut, isProd);
exports.scssSources = scssSources;