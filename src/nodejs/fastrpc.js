console.log('Hello world!');

// just to simulate browser for MapyCZ modules
if (!global.window) {
	// noinspection JSConstantReassignment JSValidateTypes
	global.window = {};
}

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
	res.setHeader('Content-Type', 'application/json; charset=UTF-8');
	res.statusCode = 200;
	if (!req.query.point) {
		res.result.message = 'Missing MapyCz "point" parameter.';
		res.result.end();
		return;
	}
	const mapyCzPlaceId = parseInt(req.query.point);
	if (mapyCzPlaceId <= 0) {
		res.result.message = 'Invalid MapyCz place/panorama ID.';
		res.result.end();
		return;
	}
	switch (parsed.pathname) {
		case '/poiagg':
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

			const placeBase64Request = placeIdToPayload(mapyCzPlaceId, mapyCzSource);
			requestMapyCz('/poiagg', placeBase64Request, function (error, response) {
				if (error) {
					res.result.message = error;
				} else {
					res.result.message = null;
					res.result.error = false;
					res.result.result = response;
				}
				res.result.end();
			});
			break;
		case '/panorpc':
			const panoramaBase64Request = panoramaIdToPayload(mapyCzPlaceId);
			requestMapyCz('/panorpc', panoramaBase64Request, function (error, firstResponse) {
				try {
					if (error) {
						throw new Error(error);
					}
					if (firstResponse.result.neighbours.length === 0) {
						throw new Error('No neighbour is available.');
					}
					requestMapyCz('/panorpc', panoramaIdToPayload(firstResponse.result.neighbours[0].near.pid), function (error, secondResponse) {
						try {
							if (error) {
								throw new Error(error);
							}
							if (secondResponse.result.neighbours.length === 0) {
								throw new Error('Neighbour\'s neighbour has no neighbour.. huh?');
							}
							for (const neighbour of secondResponse.result.neighbours) {
								if (neighbour.near.pid === mapyCzPlaceId) {
									res.result.message = null;
									res.result.error = false;
									res.result.result = neighbour;
									break;
								}
							}
							if (res.result.error) {
								res.result.error = false;
								res.result.result = firstResponse.result.neighbours[0];
								res.result.message = 'Can\'t find neighbour with PID as have original requested panorama ID. Using it\'s neighbour PID "' + firstResponse.result.neighbours[0].near.pid + '" .';
							}
						} catch (error) {
							res.result.message = error.message;
						} finally {
							res.result.end();
						}
					});
				} catch (error) {
					res.result.message = error.message;
					res.result.end();
				}
			});
			break;
		default:
			res.result.message = 'Missing /some/path';
			res.result.end();
			return;
	}
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
	const result = JAK.Base64.btoa(frpcCall);
	console.log(`Place ID "${placeId}" + source "${source}" = payload "${result}"`);
	return result
}

/**
 * All magic happens here...
 *
 * @param {number} panoramaId
 * @return {string}
 */
function panoramaIdToPayload(panoramaId) {
	const frpcCall = JAK.FRPC.serializeCall('getneighbours', [panoramaId], null);
	// const frpcCall = JAK.FRPC.serializeCall('base', ['pubt', placeId], null);
	const result = JAK.Base64.btoa(frpcCall);
	console.log(`Panorama ID "${panoramaId}" = payload "${result}"`);
	return result;
}

/**
 * Request to MapyCZ FastRPC API
 *
 * @param path {string}
 * @param {string} postContent - Magic base64 string generated from JAK.Base64.btoa()
 * @param {function(null|string, null|json)} callback
 */
function requestMapyCz(path, postContent, callback) {
	const options = {
		hostname: 'pro.mapy.cz',
		port: 443,
		path: path,
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-base64-frpc',
			'Content-Length': postContent.length,
			'Accept': 'application/json',
		}
	}
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
