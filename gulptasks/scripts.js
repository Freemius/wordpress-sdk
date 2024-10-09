const {getJsCompiler} = require('./compilers');
const jsSourceDir = 'assets/scripts';

const jsOutput = 'assets/js';

const jsSources = {
    ['nojquery.ba-postmessage']: `${jsSourceDir}/nojquery.ba-postmessage.js`,
    ['postmessage']            : `${jsSourceDir}/postmessage.js`,
    ['jquery.form']            : `${jsSourceDir}/jquery.form.js`,
};

exports.getSdkJSCompilers = (isProd) => {
    return Object.entries(jsSources).map(([fileName, source]) => {
        return getJsCompiler(
            source,
            jsOutput,
            `${fileName}.js`,
            isProd,
        );
    });
};

exports.jsSources = jsSources;