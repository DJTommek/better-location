console.log('Hello world!');

// just to simulate browser for MapyCZ modules
global.window = {};

require('../../asset/js/jak.js')
require('../../asset/js/frpc.js')
require('../../asset/js/base64.js')
const QUERYSTRING = require('querystring');
const URL = require('url');

const HTTPS = require('https');
const HTTP = require('http');
const HTTP_PORT = 3055;

const server = HTTP.createServer((req, res) => {
	res.result = {
		error: true,
		message: 'Nothing happened...',
		result: null,
		end: function () {
			const response = JSON.stringify(this);
			console.log(response);
			res.writeHead(200, {
				'Content-Type': 'application/json; charset=UTF-8'
			});
			res.end(response);
		}
	};


	const parsed = URL.parse(req.url);
	req.query = QUERYSTRING.parse(parsed.query);

	res.statusCode = 200;
	res.setHeader('Content-Type', 'application/json; charset=UTF-8');

	if (!req.query.point) {
		res.result.message = 'Missing MapyCz "point" parameter.';
		res.result.end();
		return;
	}
	const mapyCzPlaceId = parseInt(req.query.point);
	if (mapyCzPlaceId <= 0) {
		res.result.message = 'Invalid MapyCz point ID.';
		res.result.end();
		return;
	}
	if (!req.query.source) {
		res.result.message = 'Missing "source" parameter.';
		res.result.end();
		return;
	}
	const mapyCzSource = req.query.source;
	if (!mapyCzSource.match(/^[a-z]{1,20}$/)) {
		res.result.message = 'Invalid MapyCz source.';
		res.result.end();
		return;
	}

	const base64Request = placeIdToPayload(mapyCzPlaceId, mapyCzSource);
	console.log('PlaceID "' + mapyCzPlaceId + '" to magic base64: "' + base64Request + '"');
	requestMapyCz(base64Request, function (error, response) {
		if (error) {
			res.result.message = error;
		} else {
			res.result.message = null;
			res.result.error = false;
			res.result.result = response;
		}
		res.result.end();
	});
});
server.listen(HTTP_PORT, () => {
	console.log(`Server listening on port "${HTTP_PORT}" (try http://127.0.0.1:${HTTP_PORT}/).`);
});

/**
 * All magic happens here...
 *
 * @param {number} placeId
 * @param {string} source
 * @return {string}
 */
function placeIdToPayload(placeId, source) {
	const frpcCall = JAK.FRPC.serializeCall('detail', [source, placeId], null);
	// const frpcCall = JAK.FRPC.serializeCall('base', ['pubt', placeId], null);
	return JAK.Base64.btoa(frpcCall);
}

/**
 * Request to MapyCZ FastRPC API
 *
 * @param {string} postContent - Magic base64 string generated from JAK.Base64.btoa()
 * @param {function(null|string, null|json)} callback
 */
function requestMapyCz(postContent, callback) {
	const options = {
		hostname: 'pro.mapy.cz',
		port: 443,
		path: '/poiagg',
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-base64-frpc',
			'Content-Length': postContent.length,
			'Accept': 'application/json',
		}
	}
	console.log(options);
	const req = HTTPS.request(options, (res) => {
		let response = '';
		res.on('data', function (chunk) {
			response += chunk;
		});
		res.on('end', function () {
			const jsonResponse = JSON.parse(response);
			if (jsonResponse.status === 200) {
				// probably everything is OK
				callback(null, jsonResponse);
			} else {
				callback('MapyCZ API response: "' + jsonResponse.statusMessage + '"', null);
			}
		});
	});

	req.on('error', (error) => {
		callback(error, null);
	})

	req.write(postContent);
	req.end()
}