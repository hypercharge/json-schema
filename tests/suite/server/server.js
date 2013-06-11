var connect = require('connect');
var path = require('path');

var suiteDir = path.join(path.dirname(path.dirname(path.dirname(__dirname))), '/vendor/json-schema/JSON-Schema-Test-Suite/remotes');

connect.static.mime.define({'application/schema+json': ['json']});

connect()
	.use(connect.logger('dev'))
	.use(connect.static(suiteDir))
	.listen(1234);
console.log('http://localhost:1234   serving directory '+ suiteDir);