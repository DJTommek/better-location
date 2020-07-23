/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @overview Deklarace "jmenného prostoru" knihoven JAK. Dále obsahuje rozšíření
 * práce s poli dle definice JavaScriptu verze 1.6. Při použití této knihovny je
 * nutné pole vždy procházet pomocí for (var i=0; arr.length > i; i++).  
 * @author jelc 
 */ 

/**
 * @name JAK
 * @group jak
 * @namespace
 * JAK statický objekt, který se používá pro "zapouzdření"
 * všech definic a deklarací. V podmínce se nalezá pro
 * jistotu, protože může být definován ještě před svou
 * deklarací při použití slovníků, nebo konfigurací. 
 */
if (typeof(window.JAK) != 'object'){
	window.JAK = {NAME: "JAK"};
};

/**
 * generátor unikatních ID
 * @static
 * @returns {string} unikátní ID
 */
JAK.idGenerator = function(){
	this.idCnt = this.idCnt < 10000000 ? this.idCnt : 0;
	var ids = 'm' +  new Date().getTime().toString(16) +  'm' + this.idCnt.toString(16);
	this.idCnt++;
	return ids;	
};

if (!Function.prototype.bind) {
	/**
	 * ES5 Function.prototype.bind
	 * Vrací funkci zbindovanou do zadaného kontextu.
	 * Zbylé volitelné parametry jsou předány volání vnitřní funkce.
	 * @param {object} thisObj Nový kontext
	 * @returns {function}
	 */
	Function.prototype.bind = function(thisObj) { 
		var fn = this;
		var args = Array.prototype.slice.call(arguments, 1); 
		return function() { 
			return fn.apply(thisObj, args.concat(Array.prototype.slice.call(arguments))); 
		}
	}
};

if (!Date.now) {
	/** 
	 * aktuální timestamp dle ES5 - http://dailyjs.com/2010/01/07/ecmascript5-date/
	 */
	Date.now = function() { return +(new Date); }
}

/** 
 * rozšíření polí v JS 1.6 dle definice na http://dev.mozilla.org
 */
if (!Array.prototype.indexOf) { 
	Array.prototype.indexOf = function(item, from) {
	    var len = this.length;
	    var i = from || 0;
	    if (i < 0) { i += len; }
	    for (;i<len;i++) {
			if (i in this && this[i] === item) { return i; }
	    }
	    return -1;
	}
}
if (!Array.indexOf) {
	Array.indexOf = function(obj, item, from) { return Array.prototype.indexOf.call(obj, item, from); }
}

if (!Array.prototype.lastIndexOf) { 
	Array.prototype.lastIndexOf = function(item, from) {
	    var len = this.length;
		var i = (from === undefined ? len-1 : from);
		if (i < 0) { i += len; }
	    for (;i>-1;i--) {
			if (i in this && this[i] === item) { return i; }
	    }
	    return -1;
	}
}
if (!Array.lastIndexOf) {
	Array.lastIndexOf = function(obj, item, from) {
		if (arguments.length > 2) {
			return Array.prototype.lastIndexOf.call(obj, item, from);
		} else {
			return Array.prototype.lastIndexOf.call(obj, item);
		}
	}
}

if (!Array.prototype.forEach) { 
	Array.prototype.forEach = function(cb, _this) {
	    var len = this.length;
	    for (var i=0;i<len;i++) { 
			if (i in this) { cb.call(_this, this[i], i, this); }
		}
	}
}
if (!Array.forEach) { 
	Array.forEach = function(obj, cb, _this) { Array.prototype.forEach.call(obj, cb, _this); }
}

if (!Array.prototype.every) { 
	Array.prototype.every = function(cb, _this) {
	    var len = this.length;
	    for (var i=0;i<len;i++) {
			if (i in this && !cb.call(_this, this[i], i, this)) { return false; }
	    }
	    return true;
	}
}
if (!Array.every) { 
	Array.every = function(obj, cb, _this) { return Array.prototype.every.call(obj, cb, _this); }
}

if (!Array.prototype.some) { 
	Array.prototype.some = function(cb, _this) {
		var len = this.length;
		for (var i=0;i<len;i++) {
			if (i in this && cb.call(_this, this[i], i, this)) { return true; }
		}
		return false;
	}
}
if (!Array.some) { 
	Array.some = function(obj, cb, _this) { return Array.prototype.some.call(obj, cb, _this); }
}

if (!Array.prototype.map) { 
	Array.prototype.map = function(cb, _this) {
		var len = this.length;
		var res = new Array(len);
		for (var i=0;i<len;i++) {
			if (i in this) { res[i] = cb.call(_this, this[i], i, this); }
		}
		return res;
	}
}
if (!Array.map) { 
	Array.map = function(obj, cb, _this) { return Array.prototype.map.call(obj, cb, _this); }
}

if (!Array.prototype.filter) { 
	Array.prototype.filter = function(cb, _this) {
		var len = this.length;
	    var res = [];
			for (var i=0;i<len;i++) {
				if (i in this) {
					var val = this[i]; // in case fun mutates this
					if (cb.call(_this, val, i, this)) { res.push(val); }
				}
			}
	    return res;
	}
}
if (!Array.filter) { 
	Array.filter = function(obj, cb, _this) { return Array.prototype.filter.call(obj, cb, _this); }
}

/** 
 * Doplneni zadanym znakem zleva na pozadovanou delku
 */
String.prototype.lpad = function(character, count) {
	var ch = character || "0";
	var cnt = count || 2;

	var s = "";
	while (s.length < (cnt - this.length)) { s += ch; }
	s = s.substring(0, cnt-this.length);
	return s+this;
}


/** 
 * Doplneni zadanym znakem zprava na pozadovanou delku
 */
String.prototype.rpad = function(character, count) {
	var ch = character || "0";
	var cnt = count || 2;

	var s = "";
	while (s.length < (cnt - this.length)) { s += ch; }
	s = s.substring(0, cnt-this.length);
	return this+s;
}

/** 
 * Oriznuti bilych znaku ze zacatku a konce retezce
 */
String.prototype.trim = function() {
	return this.match(/^\s*([\s\S]*?)\s*$/)[1];
}
if (!String.trim) {
	String.trim = function(obj) { return String.prototype.trim.call(obj);}
}

/** 
 * Doplneni toISOString kde neni, viz https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Date/toISOString
 */
if (!Date.prototype.toISOString) {  
	(function() {  
		function pad(number) {  
			var r = String(number);  
			if ( r.length === 1 ) {  
				r = '0' + r;  
			}  
			return r;  
		}  
   
		Date.prototype.toISOString = function() {  
			return this.getUTCFullYear()  
				+ '-' + pad( this.getUTCMonth() + 1 )  
				+ '-' + pad( this.getUTCDate() )  
				+ 'T' + pad( this.getUTCHours() )  
				+ ':' + pad( this.getUTCMinutes() )  
				+ ':' + pad( this.getUTCSeconds() )  
				+ '.' + String( (this.getUTCMilliseconds()/1000).toFixed(3) ).slice( 2, 5 )  
				+ 'Z';  
		};  
	}());  
} 

Date.prototype._dayNames = ["Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle"];
Date.prototype._dayNamesShort = ["Po", "Út", "St", "Čt", "Pá", "So", "Ne"];
Date.prototype._monthNames = ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"];
Date.prototype._monthNamesShort = ["Led", "Úno", "Bře", "Dub", "Kvě", "Čer", "Črc", "Srp", "Zář", "Říj", "Lis", "Pro"];

/** 
 * Formatovani data shodne s http://php.net/date
 */
Date.prototype.format = function(str) {
	var suffixes = {
		1:"st",
		2:"nd",
		3:"rd",
		21:"st",
		22:"nd",
		23:"rd",
		31:"st"
	};
	var result = "";
	var escape = false;
	for (var i=0;i<str.length;i++) {
		var ch = str.charAt(i);
		if (escape) {
			escape = false;
			result += ch;
			continue;
		}
		switch (ch) {
			case "\\":
				if (escape) {
					escape = false;
					result += ch;
				} else {
					escape = true;
				}
			break;
			case "d": result += this.getDate().toString().lpad(); break;
			case "j": result += this.getDate(); break;
			case "w": result += this.getDay(); break;
			case "N": result += this.getDay() || 7; break;
			case "S": 
				var d = this.getDate();
				result += suffixes[d] || "th";
			break;
			case "D": result += this._dayNamesShort[(this.getDay() || 7)-1]; break;
			case "l": result += this._dayNames[(this.getDay() || 7)-1]; break;
			case "z":
				var t = this.getTime();
				var d = new Date(t);
				d.setDate(1);
				d.setMonth(0);
				var diff = t - d.getTime();
				result += diff / (1000 * 60 * 60 * 24);
			break;
			
			case "W":
				var d = new Date(this.getFullYear(), this.getMonth(), this.getDate());
				var day = d.getDay() || 7;
				d.setDate(d.getDate() + (4-day));
				var year = d.getFullYear();
				var day = Math.floor((d.getTime() - new Date(year, 0, 1, -6)) / (1000 * 60 * 60 * 24));
				result += (1 + Math.floor(day / 7)).toString().lpad();
			break;

			case "m": result += (this.getMonth()+1).toString().lpad(); break;
			case "n": result += (this.getMonth()+1); break;
			case "M": result += this._monthNamesShort[this.getMonth()]; break;
			case "F": result += this._monthNames[this.getMonth()]; break;
			case "t":
				var t = this.getTime();
				var m = this.getMonth();
				var d = new Date(t);
				var day = 0;
				do {
					day = d.getDate();
					t += 1000 * 60 * 60 * 24;
					d = new Date(t);
				} while (m == d.getMonth());
				result += day;
			break;

			case "L":
				var d = new Date(this.getTime());
				d.setDate(1);
				d.setMonth(1);
				d.setDate(29);
				result += (d.getMonth() == 1 ? "1" : "0");
			break;
			case "Y": result += this.getFullYear().toString().lpad(); break;
			case "y": result += this.getFullYear().toString().lpad().substring(2); break;

			case "a": result += (this.getHours() < 12 ? "am" : "pm"); break;
			case "A": result += (this.getHours() < 12 ? "AM" : "PM"); break;
			case "G": result += this.getHours(); break;
			case "H": result += this.getHours().toString().lpad(); break;
			case "g": result += this.getHours() % 12; break;
			case "h": result += (this.getHours() % 12).toString().lpad(); break;
			case "i": result += this.getMinutes().toString().lpad(); break;
			case "s": result += this.getSeconds().toString().lpad(); break;
			
			case "Z": result += -60*this.getTimezoneOffset(); break;
			
			case "O": 
			case "P": 
				var base = this.getTimezoneOffset()/-60;
				var o = Math.abs(base).toString().lpad();
				if (ch == "P") { o += ":"; }
				o += "00";
				result += (base >= 0 ? "+" : "-")+o;
			break;

			case "U": result += this.getTime()/1000; break; 
			case "u": result += "0"; break; 
			case "c": result += arguments.callee.call(this, "Y-m-d")+"T"+arguments.callee.call(this, "H:i:sP"); break; 
			case "r": result += arguments.callee.call(this, "D, j M Y H:i:s O"); break; 

			default: result += ch; break;
		}
	}
	return result;
}

if (!window.JSON) {
	(function(){
        var escapes = {
			'\b': '\\b',
			'\t': '\\t',
			'\n': '\\n',
			'\f': '\\f',
			'\r': '\\r',
			'"' : '\\"',
			'\\': '\\\\'
        };	
        var re = "[";
        for (var p in escapes) { re += "\\"+p; }
        re += "]";
        re = new RegExp(re, "g");

		var stringifyString = function(value) {
			var v = value.replace(re, function(ch) {
				return escapes[ch];
			});
			return '"' + v + '"';
		}
		
		var stringifyValue = function(value, replacer, space, depth) {
			var indent = new Array(depth+1).join(space);
			if (value === null) { return "null"; }

			switch (typeof(value)) {
				case "string":
					return stringifyString(value);
				break;
				
				case "boolean":
				case "number":
					return value.toString();
				break;
				
				case "object":
					var result = "";
					if (value instanceof Date) {
						result = stringifyString(value.toISOString());
					} else if (value instanceof Array) {
						result += "["; /* oteviraci se neodsazuje */
						for (var i=0;i<value.length;i++) {
							var v = value[i];

							if (i > 0) {
								result += ",";
								if (space.length) { result += "\n" + indent + space; } /* polozky se odsazuji o jednu vic */
							}
							result += arguments.callee(v, replacer, space, depth+1);
						}
						if (value.length > 1 && space.length) { result += "\n" + indent; } /* zaviraci se odsazuje na aktualni uroven */
						result += "]";

					} else {
						result += "{";
						var count = 0;
						for (var p in value) {
							var v = value[p];
							
							if (count > 0) { result += ","; }
							if (space.length) { result += "\n" + indent + space; } /* polozky se odsazuji o jednu vic */
							
							result += stringifyString(p)+":"+arguments.callee(v, replacer, space, depth+1);
							count++;
						}
						if (count > 0 && space.length) { result += "\n" + indent; } /* zaviraci se odsazuje na aktualni uroven */
						result += "}";
					}

					return result;
				break;
				
				case "undefined": 
				default:
					return "undefined";
				break;
			}
		}
		
		window.JSON = {
			parse: function(text) {
				return eval("("+text+")");
			},
			stringify: function(value, replacer, space) {
				var sp = "";
				if (typeof(space) == "number" && space > 1) {
					sp = new Array(space+1).join(" ");
				} else if (typeof(space) == "string") {
					sp = space;
				}
				
				return stringifyValue(value, replacer, sp, 0);
				
			}
		};
	})();
}
/**
 * Definice globalniho objektu "console", pokud neexistuje - abychom odchytli zapomenuta ladici volani
 */
if (!window.console) {
	window.console = {
		log: function() {}
	}
}
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @overview Statická třída sestavující dědičnost rozšiřováním prototypového objektu
 * doplňováním základních metod a testováním závislostí. 
 * @version 5.1
 * @author jelc, zara, aichi
 */   

/**
 * Konstruktor se nevyužívá. Vždy rovnou voláme metody, tedy např.: JAK.ClassMaker.makeClass(...).
 * @namespace
 * @group jak
 */    
JAK.ClassMaker = {};

/** 
 * @field {string} verze třídy 
 */
JAK.ClassMaker.VERSION = "5.1";
/** 
 * @field {string} název třídy 
 */
JAK.ClassMaker.NAME = "JAK.ClassMaker";
	
/**
 * Vlastní metoda pro vytvoření třídy, v jediném parametru se dozví informace o třídě, kterou má vytvořit.
 * @param {object} params parametry pro tvorbu nové třídy
 * @param {string} params.NAME povinný název třídy
 * @param {string} [params.VERSION="1.0"] verze třídy
 * @param {function} [params.EXTEND=false] reference na rodičovskou třídu
 * @param {function[]} [params.IMPLEMENT=[]] pole referencí na rozhraní, jež tato třída implementuje
 * @param {object[]} [params.DEPEND=[]] pole závislostí
 */
JAK.ClassMaker.makeClass = function(params) {
	var p = this._makeDefaultParams(params);
	
	var constructor = function() { /* normalni trida */
		if (this.$constructor) { this.$constructor.apply(this, arguments); }
	}

	return this._addConstructorProperties(constructor, p);
}

/**
 * Vlastní metoda pro vytvoření Jedináčka (Singleton), odlišnost od tvorby třídy přes makeClass je, 
 * že třídě vytvoří statickou metodu getInstance, která vrací právě jednu instanci a dále, že konstruktor
 * nelze zavolat pomocí new (resp. pokud je alespoň jedna instance vytvořena.) Instance je uschována do 
 * vlastnosti třídy _instance
 * @see JAK.ClassMaker.makeClass
 */ 
JAK.ClassMaker.makeSingleton = function(params) {
	var p = this._makeDefaultParams(params);
	
	var constructor = function() { /* singleton, nelze vytvaret instance */
		throw new Error("Cannot instantiate singleton class");
	}
	
	constructor._instance = null;
	constructor.getInstance = this._getInstance;

	return this._addConstructorProperties(constructor, p);
}

/**
 * Vlastní metoda pro vytvoření "třídy" charakterizující rozhranní
 * @see JAK.ClassMaker.makeClass
 */
JAK.ClassMaker.makeInterface = function(params) {
	var p = this._makeDefaultParams(params);
	
	var constructor = function() {
		throw new Error("Cannot instantiate interface");
	}
	
	return this._addConstructorProperties(constructor, p);	
}

/**
 * Vlastní metoda pro vytvoření statické třídy, tedy jmeného prostoru
 * @param {object} params parametry pro tvorbu nové třídy
 * @param {string} params.NAME povinný název třídy
 * @param {string} params.VERSION verze třídy
 */
JAK.ClassMaker.makeStatic = function(params) {
	var p = this._makeDefaultParams(params);

	var obj = {};
	obj.VERSION = p.VERSION;
	obj.NAME = p.NAME;
	return obj;
}

/**
 * Vytvoření defaultních hodnot objektu params, pokud nejsou zadané autorem
 * @param {object} params parametry pro tvorbu nové třídy 
 */ 
JAK.ClassMaker._makeDefaultParams = function(params) {
	if ('EXTEND' in params) {
		if (!params.EXTEND) {
			throw new Error("Cannot extend non-exist class");
		}
		if (!('NAME' in params.EXTEND)) {
			throw new Error("Cannot extend non-JAK class");
		}
	}

	params.NAME = params.NAME || false;
	params.VERSION = params.VERSION || "1.0";
	params.EXTEND = params.EXTEND || false;
	params.IMPLEMENT = params.IMPLEMENT || [];
	params.DEPEND = params.DEPEND || [];
	
	/* implement muze byt tez jeden prvek */
	if (!(params.IMPLEMENT instanceof Array)) { params.IMPLEMENT = [params.IMPLEMENT]; }
	
	this._preMakeTests(params);
	
	return params;
}

/**
 * Otestování parametrů pro tvorbu třídy
 * @param {object} params parametry pro tvorbu nové třídy 
 */ 
JAK.ClassMaker._preMakeTests = function(params) {
    if (!params.NAME) { throw new Error("No NAME passed to JAK.ClassMaker.makeClass()"); }
	
	/* test zavislosti */
	var result = false;
	if (result = this._testDepend(params.DEPEND)) { throw new Error("Dependency error in class " + params.NAME + " ("+result+")"); }
}

/**
 * Vytvořenému konstruktoru nové třídy musíme do vínku dát výchozí hodnoty a metody
 */ 
JAK.ClassMaker._addConstructorProperties = function(constructor, params) {
	/* staticke vlastnosti */
	for (var p in params) { constructor[p] = params[p]; }
	
	/* zdedit */
	this._setInheritance(constructor);
	
	/* classMaker dava instancim do vinku tyto vlastnosti a metody */
	constructor.prototype.constructor = constructor;
	constructor.prototype.$super = this._$super;
	
	return constructor;	
}

/**
 * Statická metoda pro všechny singletony
 */
JAK.ClassMaker._getInstance = function() {
	if (!this._instance) { 
		var tmp = function() {};
		tmp.prototype = this.prototype;
		this._instance = new tmp(); 
		if ("$constructor" in this.prototype) { this._instance.$constructor(); }
	}
	return this._instance;
}
	
/**
 * Volá vlastní kopírování prototypových vlastností jednotlivých rodičů
 * @param {array} extend pole rodicovskych trid
*/
JAK.ClassMaker._setInheritance = function(constructor) {
	if (constructor.EXTEND) { this._makeInheritance(constructor, constructor.EXTEND); }
	for (var i=0; i<constructor.IMPLEMENT.length; i++) {
		this._makeInheritance(constructor, constructor.IMPLEMENT[i], true);
	}
}

/**
 * Provádí vlastní kopírovaní prototypových vlastností z rodiče do potomka 
 * pokud je prototypová vlastnost typu object zavolá metodu, která se pokusí
 * vytvořit hlubokou kopii teto vlastnosti
 * @param {object} constructor Potomek, jehož nové prototypové vlastnosti nastavujeme
 * @param {object} parent Rodič, z jehož vlastnosti 'protype' budeme kopírovat	  	 
 * @param {bool} noSuper Je-li true, jen kopírujeme vlasnosti (IMPLEMENT)
*/
JAK.ClassMaker._makeInheritance = function(constructor, parent, noSuper){
	/* nastavit funkcim predka referenci na predka */
	for (var p in parent.prototype) {
		var item = parent.prototype[p];
		if (typeof(item) != "function") { continue; }
		if (!item.owner) { item.owner = parent; }
	}

	if (!noSuper) { /* extend */
		var tmp = function(){}; 
		tmp.prototype = parent.prototype;
		constructor.prototype = new tmp();
		for (var p in parent.prototype) {
			if (typeof parent.prototype[p] != "object") { continue; }
			constructor.prototype[p] = JSON.parse(JSON.stringify(parent.prototype[p]));
		}
		return;
	}

	for (var p in parent.prototype) { /* implement */
		if (typeof parent.prototype[p] == "object") {
			constructor.prototype[p] = JSON.parse(JSON.stringify(parent.prototype[p]));
		} else {
			constructor.prototype[p] = parent.prototype[p];
		}
	}
}
	
/**
 * Testuje závislosti vytvářené třídy, pokud jsou nastavené
 * @param {array} depend Pole závislostí, ktere chceme otestovat
 * @returns {bool|string} false = ok, string = error  
*/
JAK.ClassMaker._testDepend = function(depend){
	for(var i = 0; i < depend.length; i++) {
		var item = depend[i];
		if (!item.sClass) { return "Unsatisfied dependency"; }
		if (!item.ver) { return "Version not specified in dependency"; }
		var depMajor = item.sClass.VERSION.split('.')[0];
		var claMajor = item.ver.split('.')[0];
		if (depMajor != claMajor) { return "Version conflict in "+item.sClass.NAME; }
	}
	return false;
}

/**
 * Další pokus o volání předka. Přímo volá stejně pojmenovanou metodu předka a předá jí zadané parametry.
 */
JAK.ClassMaker._$super = function() {
	var caller = arguments.callee.caller; /* nefunguje v Opere < 9.6 ! */
	if (!caller) { throw new Error("Function.prototype.caller not supported"); }
	
	var owner = caller.owner || this.constructor; /* toto je trida, kde jsme "ted" */

	var callerName = false;
	for (var name in owner.prototype) {
		if (owner.prototype[name] == caller) { callerName = name; break; }
	}
	if (!callerName) { throw new Error("Cannot find supplied method in constructor"); }
	
	var parent = owner.EXTEND;
	if (!parent) { throw new Error("No super-class available"); }
	if (!parent.prototype[callerName]) { throw new Error("Super-class doesn't have method '"+callerName+"'"); }

	var func = parent.prototype[callerName];
	return func.apply(this, arguments);
}
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @overview Třída sloužící ke zpracovavaní udalostí a časovačů poskytovaných DOM modelem.
 * @version 3.1
 * @author jelc, zara
 */   

/**
 * Jmenný prostor pro správu událostí
 * @group jak
 * @namespace
 */   
JAK.Events = JAK.ClassMaker.makeStatic({
	NAME: "JAK.Events",
	VERSION: "3.0"
});

/**
 * do této vlastnosti ukládáme všechny události pro odvěšení
 */ 
JAK.Events._eventFolder = {};

/**
 * vnitřní proměnné pro onDomRady()
 * @private 
 */ 
JAK.Events._domReadyTimer = null;
JAK.Events._domReadyCallback = [];   //zasobnik s objekty a jejich metodami, ktere chci zavolat po nastoleni udalosti
JAK.Events._domReadyAlreadyRun = false;/*ondomready je odchytavano specificky pro ruzne browsery a na konci je window.onload, tak aby se nespustilo 2x*/
JAK.Events._windowLoadListenerId = false; /*v nekterych prohlizecich pouzivame listener, pro jeho odveseni sem schovavam jeho id*/

/**
 * metoda kterou použijeme, pokud chceme navěsit vlastní kód na událost, kdy je DOM strom připraven k použití.
 * Je možné navěsit libovolný počet volaných funkcí.   
 * @param {object} obj objekt ve kterém se bude událost zachytávat, pokud je volána
 * globalní funkce musí byt 'window' případně 'document' 
 * @param {function || string} func funkce, která se bude provádět jako posluchač  
 */ 
JAK.Events.onDomReady = function(obj, func) {
	JAK.Events._domReadyCallback[JAK.Events._domReadyCallback.length] = {obj: obj, func: func}
	JAK.Events._onDomReady();
}

/**
 * vnitrni metoda volana z onDomReady, dulezite kvuli volani bez parametru pro IE, abychom v tom timeoutu mohli volat sama sebe
 * @private
 */ 
JAK.Events._onDomReady = function() {
	if((/Safari/i.test(navigator.userAgent)) || (/WebKit|Khtml/i.test(navigator.userAgent))){ //safari, konqueror
		JAK.Events._domReadyTimer=setInterval(function(){
			if(/loaded|complete/.test(document.readyState)){
			    clearInterval(JAK.Events._domReadyTimer);
			    JAK.Events._domReady(); // zavolani cilove metody
			}}, 10);
	} else if (document.all && !window.opera){ //IE
		//v IE
		//nejsme v ramu
		if (window.parent == window) {
			try {
				// Diego Perini trik bez document.write, vice viz http://javascript.nwbox.com/IEContentLoaded/
				document.documentElement.doScroll("left"); //test moznosti scrolovat, scrolovani jde dle msdn az po content load
			} catch( error ) {
				setTimeout( arguments.callee, 1 ); //nejde, tak volam sama sebe
				return;
			}
			// uz to proslo
			JAK.Events._domReady(); // zavolani cilove metody
		
			//v ramu horni kod nefunguje, protoze document.documentElement je jen stranka s framesetem a ten je rychle nacten ale v ram nacten a byt redy nemusi 
		} else {
			JAK.Events._windowLoadListenerId = JAK.Events.addListener(window, 'load', window, function(){JAK.Events._domReady();});
		}
	} else 	if (document.addEventListener) { //FF, opera
		//JAK.Events._domReadyAlreadyRun = true;
  		document.addEventListener("DOMContentLoaded", JAK.Events._domReady, false); //FF, Opera ma specifickou udalost 
  	} else {
	  	//pokud nic z toho tak dame jeste onload alespon :-)
	  	JAK.Events._windowLoadListenerId = JAK.Events.addListener(window, 'load', window, function(){JAK.Events._domReady();});
	}
}

/**
 * metoda, která je volána z JAK.Events.onDomReady když je dom READY, tato metoda volá 
 * na předaném objektu funkci která byla zadaná 
 * @private
 */ 
JAK.Events._domReady = function() {
	//zaruceni ze se to spusti jen jednou, tedy tehdy kdyz je _domReadyAlreadyRun=false
	if (!JAK.Events._domReadyAlreadyRun) {
		//metoda byla opravdu zavolana
		JAK.Events._domReadyAlreadyRun = true;
	
		//pro FF, operu odvesim udalost
		if (document.addEventListener) {
			document.removeEventListener("DOMContentLoaded", JAK.Events._domReady, true);
		}
		//odveseni udalosti window.onload
		if (JAK.Events._windowLoadListenerId) {
			JAK.Events.removeListener(JAK.Events._windowLoadListenerId);
			JAK.Events._windowLoadListenerId = false;
		}
		
		//vlastni volani metody objektu
		for(var i=0; i < JAK.Events._domReadyCallback.length; i++) {
			var callback =  JAK.Events._domReadyCallback[i];
			if (typeof callback.func == 'string') {
				callback.obj[callback.func]();
			} else {
				callback.func.apply(callback.obj, []);
			}
		}
		//cisteni, uz nechceme zadny odkazy na objekty a funkce
		JAK.Events._domReadyCallback = [];
	}
	
}

/**
 * Zavěšuje posluchače na danou událost, vytváří a ukládá si anonymní funkci
 * která provede vlastní volání registroveného posluchače tak aby se provedl ve správném
 * oboru platnosti. (this uvnitř posluchače se bude vztahovat k objektu jehož je naslouchající funkce metodou  
 * a jako parametry se jí předá odkaz na událost, která byla zachycena a element, na kterém se naslouchalo.)<br/>
 * <strong>POZOR!</strong> Dle specifikace se nevolá capture posluchač, pokud je navěšený na prvek, 
 * na kterém událost vznikla (jen na jeho předcích). 
 * Dodržuje to však pouze Opera, Gecko ne (viz https://bugzilla.mozilla.org/show_bug.cgi?id=235441).
 * @param {node} elm element, který událost zachytává
 * @param {string} type název události (bez předpony "on"); možno zadat víc událostí naráz oddělených mezerami
 * @param {object} obj objekt, jehož metodu budeme volat 
 * @param {function || string} func funkce, která se bude provádět jako posluchač
 * <em>string</em> pokud jde o metodu <em>obj</em> nebo reference na funkci, která se zavolá
 * jako metoda <em>obj</em>  
 * @param {boolean} capture hodnata použitá jako argument capture pro DOM zachytávání, pro IE je ignorována 
 * @returns {string} identifikátor handleru v <em>_eventFolder</em>, prostřednictvím kterého se událost odvěšuje
 * @throws {error} Events.addListener: arguments[3] must be method of arguments[2]
 */
JAK.Events.addListener = function(elm, type, obj, func, capture) {
	var capture = capture || false;
	var action = null;
	var id = JAK.idGenerator();

	if (arguments.length > 3) { /* funkce zadana jako 4. parametr */
		if (typeof(func) == "string" && typeof(obj[func]) != "function") {
			throw new Error("Events.addListener: arguments[3] must be method of arguments[2]");
		}
		action = this._getMethod(obj, func, elm, id);
	} else { /* funkce zadana jako 3. parametr */
		action = this._getMethod(window, obj, elm, id);
	}
	
	this._addListener(elm, type, action, capture);

	this._eventFolder[id] = {
		elm: elm,
		type: type,
		action: action, 
		capture: capture, 
		obj: obj, /* kvuli visualevents */
		func: func /* kvuli visualevents */
	};

	return id;
}

/**
 * Vlastní zavěšení posluchače bud DOM kompatibilně, nebo přes attachEvent pro IE 
 * @param {node} elm element, který událost zachytává
 * @param {string} type typ události bez předpony "on"; možno zadat víc událostí naráz oddělených mezerami
 * @param {function} action funkce/metoda která se bude provádět
 * @param {boolean} capture hodnota použitá jako argument capture pro DOM zachytávání
 * @returns {array} obsahující argumenty funkce ve shodném pořadí 
 */    
JAK.Events._addListener = function(elm, type, action, capture) {
	var types = type.split(" ");
	
	for (var i=0;i<types.length;i++) {
		var t = types[i];
		if (document.addEventListener) {
			elm.addEventListener(t, action, capture);
		} else if (document.attachEvent) {
			elm.attachEvent('on'+t, action);
		} else {
			throw new Error("This browser can not handle events");
		}
	}
}

/**
 * Vytváří funkci/metodu, která bude fungovat jako posluchač události tak
 * aby předaná metoda byla zpracovávána ve správnem oboru platnosti, this bude
 * objekt který ma naslouchat, požadované metodě předává objekt události a element na
 * kterém se naslouchalo
 * @param {object} obj objekt v jehož oboru platnosti se vykoná <em>func</em> po zachycení události
 * @param {function} func funkce/metoda, u které chceme aby use dálost zpracovávala
 * @param {node} elm Element na kterém se poslouchá (druhý parametr callbacku)
 * @param {string} id ID události (třetí parametr callbacku)
 * @returns {function} anonymní funkce, volaná se správnými parametry ve správném kontextu
 */    
JAK.Events._getMethod = function(obj, func, elm, id) {
	var f = (typeof(func) == "string" ? obj[func] : func);
	return function(e) {
		return f.call(obj, e, elm, id);
	}
}

/**
 * Odebírání posluchačů události zadáním <em>id</em>, které vrací medoda <em>addListener</em>
 * @param {id} id ID události
 */    
JAK.Events.removeListener = function(id) {
	if (!(id in this._eventFolder)) { throw new Error("Cannot remove non-existent event ID '"+id+"'"); }

	var obj = this._eventFolder[id];
	this._removeListener(obj.elm, obj.type, obj.action, obj.capture);
	delete this._eventFolder[id];
}

/**
 * provádí skutečné odvěšení posluchačů DOM kompatibilně či pro IE
 * @param {object} elm element na kterém se naslouchalo
 * @param {string} type událost, která se zachytávala; možno zadat víc událostí naráz oddělených mezerami
 * @param {function} action skutečná funkce, která zpracovávala událost
 * @param  {boolean} capture pro DOM zpracovávání stejna hodota jako při zavěšování
 */    
JAK.Events._removeListener = function(elm, type, action, capture) {
	var types = type.split(" ");
	
	for (var i=0;i<types.length;i++) {
		var t = types[i];
		if (document.removeEventListener) {
			elm.removeEventListener(t, action, capture);
		} else if (document.detachEvent) {
			elm.detachEvent('on'+t, action);
		}
	}
}

/**
 * provede odvěšení událostí podle jejich <em>id</em> uložených v poli
 * @param {array} array pole ID událostí jak je vrací metoda <em>addListener</em>
 */  
JAK.Events.removeListeners = function(array) {
	while(array.length) {
		this.removeListener(array.shift());
	}
}


/**
 * provede odvěšení všech posluchačů, kteří jsou uloženi v <em>_eventFolder</em>
 */   
JAK.Events.removeAllListeners = function() {
	for (var id in this._eventFolder) { this.removeListener(id); }
}

/**
 * zastaví probublávaní události stromem dokumentu
 * @param {object} e zpracovávaná událost 
 */  
JAK.Events.stopEvent = function(e) {
	var e = e || window.event;
	if (e.stopPropagation){
		e.stopPropagation();
	} else { 
		e.cancelBubble = true;
	}
}

/**
 * zruší výchozí akce (definované klientem) pro danou událost (např. prokliknutí odkazu)
 * @param {object} e zpracovávaná událost 
 */   
JAK.Events.cancelDef = function(e) {
	var e = e || window.event;
	if(e.preventDefault) {
		e.preventDefault();
	} else {
		e.returnValue = false;
	}
}

/**
 * vrací cíl události, tedy na kterém DOM elementu byla vyvolána.
 * @param {object} e událost 
 */  
JAK.Events.getTarget = function(e) {
	var e = e || window.event;
	return e.target || e.srcElement; 
}

/**
 * metoda vrací strukturovaný objekt s informacemi o nabindovaných událostech. struktura je vhodná pro bookmarklet
 * Visual Event (http://www.sprymedia.co.uk/article/Visual+Event) od Allana Jardine. Po spuštění jeho JS bookmarkletu
 * jsou navěšené události vizualizovány na dané stránce
 */
JAK.Events.getInfo = function() {
	var output = [];

	var nodes = [];
	var events = [];

	for (var id in JAK.Events._eventFolder) {
		var o = JAK.Events._eventFolder[id];
		var elm = o.elm;

		var index = nodes.indexOf(elm);
		if (index == -1) {
			index = nodes.push(elm) - 1;
			events[index] = [];
		}

		events[index].push(o);
	}

	for (var i=0; i<nodes.length; i++) {
		var listeners = [];
		for (var j=0; j<events[i].length; j++) {
			var o = events[i][j];

			var obj = o.obj || window;
			var func = o.func || o.obj;

			listeners.push({
				'sType': o.type,
				'bRemoved': false,
				'sFunction':  (obj != window && obj.constructor ? '['+obj.constructor.NAME+']' : '') + 
					(typeof(func) == 'string' ? '.'+func+' = '+ obj[func].toString() : ' '+func.toString())
			});
		}

		output.push({
			'sSource': 'JAK',
			'nNode': nodes[i],
			'aListeners': listeners
		});
	}

	return output;
}
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @overview Detekce klientského prostředí v závislosti na vlastnostech JavaScriptu
 * (pokud je to možné, jinak dle vlastnosti navigator.userAgent).
 * @version 3.0
 * @author jelc, zara
 */   

/**
 * Statická třída obsahující vlastnosti <em>platform</em>, <em>client</em>,  
 * <em>version</em> a <em>agent</em>, které určují uživatelovo prostředí
 * @namespace
 * @group jak
 */
JAK.Browser = JAK.ClassMaker.makeStatic({
	NAME: "JAK.Browser",
	VERSION: "3.0"
});

/** @field {string} platform system uzivatele */
JAK.Browser.platform = '';
/** @field {string} client prohlizec uzivatele */
JAK.Browser.client = '';
/** @field {string} version verze prohlizece */
JAK.Browser.version = 0;
/** @field {string} agent hodnota systemove promene "navigator.userAgent" */
JAK.Browser.agent = '';
/** @field {object} mouse objekt s vlastnostmi left, right, middle které lze použít k identifikaci stisknutého tlačítka myši */
JAK.Browser.mouse = {};

/**
 * Zjistuje system uzivatele
 * @private
 * @returns {string} ktery popisuje pouzivany system:
 * <ul>
 * <li>nix - Linux, BSD apod.</li>
 * <li>mac - Apple</li>
 * <li>win - Windows pro PC</li>
 * <li>oth - vsechno ostatni</li>  
 * </ul>    
 *
 */   
JAK.Browser._getPlatform = function(){
	if((this._agent.indexOf('iPhone') != -1)
	|| (this._agent.indexOf('iPod') != -1)
	|| (this._agent.indexOf('iPad') != -1)){
		return 'ios';
	} else if(this._agent.indexOf('Android') != -1){
		return 'and';
	} else if((this._agent.indexOf('X11') != -1)
	|| (this._agent.indexOf('Linux') != -1)){
		return 'nix';
	} else if(this._agent.indexOf('Mac') != -1){
		return 'mac';
	} else if(this._agent.indexOf('Win') != -1){
		return 'win';
	} else {
		return 'oth';
	}
};

/**
 * Zjistuje typ prohlizece
 * @private
 * @returns {string} ktery popisuje pouzivany prohlizec
 * <ul>
 * <li>opera - Opera</li>
 * <li>ie - Internet Explorer</li>
 * <li>gecko - Mozilla like</li>
 * <li>konqueror - Konqueror</li>  
 * <li>safari - Safari</li>  
 * <li>chrome - Google Chrome</li>  
 * <li>oth - vsechno ostatni/neznamy</li>  
 * </ul>  
 */   
JAK.Browser._getClient = function(){
	if (window.opera) {
		return "opera";
	} else if (window.chrome) {
		return "chrome";
	} else if(document.attachEvent && (typeof navigator.systemLanguage != "undefined")) {
		return "ie";
	} else if (document.getAnonymousElementByAttribute) {
		return "gecko";
	} else if (this._agent.indexOf("KHTML") != -1) {
		if (this._vendor == "KDE") {
			return "konqueror";
		} else {
			return "safari";
		}
	} else {
		return "oth";
	}
};

/**
 * Nastavuje identifikaci leveho a praveho tlacitka mysi
 * @private 
 * @returns {object} jako asociativni pole s vlastnostmi
 * <em>left</em> a <em>right</em>, ktere obsahuji ciselny identifikator
 * stisknuteho tlacitka mysi jak ho klient vraci ve vlastnosti udalosti
 * <em>e.button</em>
 */
JAK.Browser._getMouse = function(){
	var left;
	var right;
	var middle;
	if (JAK.Browser.client == 'ie' && parseFloat(JAK.Browser.version) < 9) {
		left = 1;
		middle = 4;
		right = 2;
	} else if(JAK.Browser.client == 'konqueror') {
		var ver = parseFloat(JAK.Browser.version);
		if(ver < 4 ) {
			left = 1;
			middle = 4;
			right = 2;
		} else {
			left = 0;
			middle = 1;
			right = 2;
		}
	} else if((JAK.Browser.client == 'opera') && (JAK.Browser.version > 7) && (JAK.Browser.version < 9)) {
		left = 1;
		middle = 4;
		right = 2;
	} else if (JAK.Browser.client == 'safari'){
		if (parseInt(JAK.Browser.version) > 2) {
			left = 0;
			middle = 0;
			right = 2;
		} else {
			left = 1;
			middle = 1;
			right = 2;
		}
	} else {
		left = 0;
		middle = 1;
		right = 2;
	}
	
	return {left:left,right:right, middle:middle};	
}

/**
 * Zjistuje verzi daneho prohlizece, detekovaneho metodou "_getClient"
 * @private
 * @returns {string} navratova hodnota metod jejich nazev je slozeny z retezcu
 * '_get_' + vlastnost <em>client</em>  + '_ver'
 * @example  <pre>
 * pro Internet Exlporer je volana metoda <em>this._get_ie_ver()</em>
 *</pre>    
 */   
JAK.Browser._getVersion = function(){
	var out = 0;
	var fncName = '_get_' + this.client + '_ver';
	
	if(typeof this[fncName] == 'function'){
		return this[fncName]();
	} else {
		return 0;
	}
};

/**
 * Detekce verze Internet Exploreru
 * @private
 * @returns {string} verze prohlizece od 5.0 do 7 (IE 8 bude detekovano jako 7)
 */   
JAK.Browser._get_ie_ver = function(){
	if(typeof Function.prototype.call != 'undefined'){
		if("draggable" in document.createElement('div')) {
			return '10';
		} else if (document.addEventListener) {
			return '9';
		} else if (window.XDomainRequest) {
			return '8';
		} else if(window.XMLHttpRequest){
			return '7';
		} else if (typeof document.doctype == 'object'){
			return '6';
		} else {
			return '5.5';
		}
	} else {
		return '5.0';
	}
};

/**
 * Detekce verze Opery
 * Od 6 do aktualni verze. od verze 7.6+ je podporovana vlastnost
 * window.opera.version() vracejici aktualni verzi, napr. 9.63  
 * @see http://www.howtocreate.co.uk/operaStuff/operaObject.html 
 * @private
 * @returns {string} verze prohlizece 
 */  
JAK.Browser._get_opera_ver = function(){
	if(window.opera.version){
		return window.opera.version();
	} else { 
		if(document.createComment){
			return '7';
		} else {
			return '6';
		}
	}
};

/**
 * Detekce verze Gecko prohlizecu
 * @private
 * @returns {string} verze prohlizece od 1.5 do 7 (> 7 bude detekovano jako 7)
 */ 
JAK.Browser._get_gecko_ver = function() {
	if ("textOverflow" in document.createElement("div").style) { 
		return "7";
	} else if (window.EventSource) { 
		return "6";
	} else if ("onloadend" in new XMLHttpRequest()) { 
		return "5";
	} else if (history.pushState) { 
		return "4";
	} else if (document.getBoxObjectFor === undefined) { 
		return "3.6";
	} else if (navigator.geolocation) {
		return "3.5";
	} else if (document.getElementsByClassName) {
		return "3";
	} else if (window.external){
		return "2";
	} else {
		return "1.5";
	}
};

/**
 * Detekce verze Konqueroru
 * @private
 * @returns {string} verze prohlizece na zaklade hodnot uvedenych v navigator.userAgent
 * detekuji se prvni dve cisla (3.4,3.5,3.6 atd...) 
 */ 
JAK.Browser._get_konqueror_ver = function(){
	var num = this._agent.indexOf('KHTML') + 6;
	var part =  this._agent.substring(num);
	var end = part.indexOf(' ')
	var x = part.substring(0,end - 2);
	return x;
	
};

/**
 * Detekce verze Safari
 * @private
 * @returns {string} verze
 */ 
JAK.Browser._get_safari_ver = function(){
	var ver = this._agent.match(/version\/([0-9]+)/i);
	return (ver ? ver[1] : "1");
};

/**
 * Detekce verze Google Chrome
 * @private
 * @returns {string} verze
 */ 
JAK.Browser._get_chrome_ver = function(){
	var ver = this._agent.match(/Chrome\/([0-9]+)/i);
	return (ver ? ver[1] : null);
};

/**
 * Je tento prohlížeč moc starý na používání JAKu?
 */
JAK.Browser.isOld = function() {
	if (this.client == "ie" && parseFloat(this.version) <= 5.5) { return true; }
	if (this.client == "opera" && parseFloat(this.version) < 9.5) { return true; }
	if (this.client == "gecko" && parseFloat(this.version) < 2) { return true; }
	/* *
	 * if (this.client == "safari" && parseFloat(this.version) < 2) { return true; } 
	 * Zahozeno, aby se predeslo konfliktum s webkit-based browsery
	 */ 
	if (this.client == "konqueror" && parseFloat(this.version) < 3.5) { return true; }
	if (!document.documentElement) { return true; }
	if (!document.documentElement.addEventListener && !document.documentElement.attachEvent) { return true; }
	var f = function() {};
	if (!f.call || !f.apply) { return true; }
	return false;
}

/**
 * Implicitní konkstruktor, je volán při načtení skriptu 
 */   
JAK.Browser.getBrowser = function(){
	this._agent = this.agent = navigator.userAgent;
	this._platform = navigator.platform;
	this._vendor = navigator.vendor;
	this.platform = this._getPlatform();
	this.client = this._getClient();
	this.version = this._getVersion();
	this.mouse = this._getMouse();
};
JAK.Browser.getBrowser();
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @overview Statická třída posytující některé praktické metody na úpravy a práci s DOM stromem, např. vytváření a získávání elementů.
 * @version 5.0
 * @author zara, koko, jelc
 */

/**
 * Statický konstruktor, nemá smysl vytvářet jeho instance.
 * @namespace
 * @group jak
 */
JAK.DOM = JAK.ClassMaker.makeStatic({
	NAME: "JAK.DOM",
	VERSION: "5.0"
});

/**
 * Vytvoří DOM node, je možné rovnou zadat CSS třídu a id.
 * Etymologie: cel = cREATE elEMENT
 * @param {string} tagName jméno tagu (lowercase)
 * @param {string} className název CSS tříd(y)
 * @param {string} id id uzlu
 * @param {object} [doc] dokument, v jehož kontextu se node vyrobí (default: document)
 * @returns {node}
 */
JAK.cel = function(tagName, className, id, doc) {
	var d = doc || document;
	var node = d.createElement(tagName);
	if (className) { node.className = className; }
	if (id) { node.id = id; }
	return node;
}
	
/**
 * Vytvoří DOM node, je možné rovnou zadat vlastnosti a css vlastnosti.
 * Etymologie: mel = mAKE elEMENT
 * @param {string} tagName jméno tagu (lowercase)
 * @param {object} properties asociativní pole vlastností a jejich hodnot
 * @param {object} styles asociativní pole CSS vlastností a jejich hodnot
 * @param {object} [doc] dokument, v jehož kontextu se node vyrobí (default: document)
 * @returns {node}
 */
JAK.mel = function(tagName, properties, styles, doc) {
	var d = doc || document;
	var node = d.createElement(tagName);
	if (properties) {
		for (var p in properties) { node[p] = properties[p]; }
	}
	if (styles) { JAK.DOM.setStyle(node, styles); }
	return node;
}

/**
 * Alias pro document.createTextNode.
 * Etymologie: ctext = cREATE text
 * @param {string} str řetězec s textem
 * @param {object} doc dokument, v jehož kontextu se node vyrobí (default: document)
 * @returns {node}
 */
JAK.ctext = function(str, doc) {
	var d = doc || document;
	return d.createTextNode(str);
}
	
/**
 * Zjednodušený přístup k metodě DOM document.getElementById.
 * Etymologie: gel = gET elEMENT
 * @param {string || node} id id HTML elementu, který chceme získat nebo element
 * @param {object} [doc] dokument, v jehož kontextu se node vyrobí (default: document)
 * @returns {node} HTML element s id = id, pokud existuje, NEBO element specifikovaný jako parametr
 */
 JAK.gel = function(id, doc) {
	var d = doc || document;
	if (typeof(id) == "string") {
		return d.getElementById(id);
	} else { return id; }
}


/**
 * Vrací pole prvků vyhovujících zadanému CSS1 selektoru
 * @param {string} query CSS1 selektor
 * @param {node} [root=document] Rodičovský prvek
 * @returns {node[]}
 */
JAK.query = function(query, root) {
	
	/* profiltruje nodeset podle idcek a atributu */
	var filterNodes = function(nodes, attributes) {
		var arr = [];
		for (var i=0;i<nodes.length;i++) {
			var node = nodes[i];
			var ok = true;
			for (var j=0;j<attributes.length;j++) {
				var attrib = attributes[j];
				var ch = attrib.charAt(0);
				var value = attrib.substr(1).toLowerCase();
				if (ch == "#" && value != node.id.toLowerCase()) { ok = false; }
				if (ch == "." && !JAK.DOM.hasClass(node, value)) { ok = false; }
			}
			if (ok) { arr.push(node); }
		}
		return arr;
	}
	
	var result = [];
	root = root || document;
	
	var selectors = query.split(","); /* sjednoceni */
	while (selectors.length) {
		var selector = selectors.shift().trim(); /* jeden css selektor */
		var parts = selector.split(/ +/);
		
		var candidates = [root]; /* zde udrzujeme vsechny, kteri zatim vyhovuji */
		
		for (var i=0;i<parts.length;i++) { /* vsechny casti oddelene mezerou */
			var newCandidates = [];
			var part = parts[i];
			
			var tagName = part.match(/^[a-z0-9]*/i)[0] || "*"; /* nazev uzlu nebo "*" */
			var attributes = part.match(/[\.#][^\.#]+/g) || []; /* pole idcek a/nebo class */
			
			while (candidates.length) { /* vezmu vsechny co zatim prosli */
				var candidate = candidates.shift();
				var nodes = candidate.getElementsByTagName(tagName); /* vsichni jeho vyhovujici potomci */
				
				newCandidates = newCandidates.concat(filterNodes(nodes, attributes)); /* prosli touto iteraci */
			}
			
			candidates = newCandidates;
		}
		
		for (var i=0;i<candidates.length;i++) {
			var c = candidates[i];
			if (result.indexOf(c) == -1) { result.push(c); }
		}
	}

	return result;	
}

/**
 * Propoji zadané DOM uzly
 * @param {Array} pole1...poleN libovolný počet polí; pro každé pole se vezme jeho první prvek a ostatní 
 *   se mu navěsí jako potomci
 */
JAK.DOM.append = function() { /* takes variable amount of arrays */
	for (var i=0;i<arguments.length;i++) {
		var arr = arguments[i];
		var head = arr[0];
		for (var j=1;j<arr.length;j++) {
			head.appendChild(arr[j]);
		}
	}
}

/**
 * Otestuje, má-li zadany DOM uzel danou CSS třídu
 * @param {Object} element DOM uzel
 * @param {String} className CSS třída
 * @return {bool} true|false
 */
JAK.DOM.hasClass = function(element,className) {
	var arr = element.className.split(" ");
	for (var i=0;i<arr.length;i++) { 
		if (arr[i].toLowerCase() == className.toLowerCase()) { return true; } 
	}
	return false;
}

/**
 * Přidá DOM uzlu CSS třídu. Pokud ji již má, pak neudělá nic.
 * @param {Object} element DOM uzel
 * @param {String} className CSS třída
 */
JAK.DOM.addClass = function(element,className) {
	if (JAK.DOM.hasClass(element,className)) { return; }
	element.className += " "+className;
}

/**
 * Odebere DOM uzlu CSS třídu. Pokud ji nemá, neudělá nic.
 * @param {Object} element DOM uzel
 * @param {String} className CSS třída
 */
JAK.DOM.removeClass = function(element,className) {
	var names = element.className.split(" ");
	var newClassArr = [];
	for (var i=0;i<names.length;i++) {
		if (names[i].toLowerCase() != className.toLowerCase()) { newClassArr.push(names[i]); }
	}
	element.className = newClassArr.join(" ");
}

/**
 * Vymaže (removeChild) všechny potomky daného DOM uzlu
 * @param {Object} element DOM uzel
 */
JAK.DOM.clear = function(element) {
	while (element.firstChild) { element.removeChild(element.firstChild); }
}

/**
 * vrací velikost dokumentu, lze použít ve standardním i quirk módu 
 * @returns {object} s vlastnostmi:
 * <ul><li><em>width</em> - šířka dokumentu</li><li><em>height</em> - výška dokumentu</li></ul> 
 */    
JAK.DOM.getDocSize = function(){
	var x = 0;
	var y = 0;		
	if (document.compatMode != 'BackCompat') {
		
		if(document.documentElement.clientWidth && JAK.Browser.client != 'opera'){
			x = document.documentElement.clientWidth;
			y = document.documentElement.clientHeight;
		} else if(JAK.Browser.client == 'opera') {
			if(parseFloat(JAK.Browser.version) < 9.5){
				x = document.body.clientWidth;
				y = document.body.clientHeight;
			} else {
				x = document.documentElement.clientWidth;
				y = document.documentElement.clientHeight;
			}
		} 
		
		if ((JAK.Browser.client == 'safari') || (JAK.Browser.client == 'konqueror')){
			y = window.innerHeight; 
		}
	} else {
		x = document.body.clientWidth;
		y = document.body.clientHeight;
	}
	
	return {width:x,height:y};
};

/**
 * vrací polohu "obj" ve stránce nebo uvnitř objektu který předám jako druhý 
 * argument
 * @param {object} obj HTML element, jehož pozici chci zjistit
 * @param {object} [ref] <strong>volitelný</strong> HTML element, vůči kterému chci zjistit pozici <em>obj</em>, element musí být jeho rodič
 * @param {bool} fixed <strong>volitelný</strong> flag, má-li se brát ohled na "fixed" prvky
 * @returns {object} s vlastnostmi :
 * <ul><li><em>left</em>(px) - horizontální pozice prvku</li><li><em>top</em>(px) - vertikální pozice prvku</li></ul> 
 */
JAK.DOM.getBoxPosition = function(obj, ref){
	var top = 0;
	var left = 0;
	var refBox = ref || obj.ownerDocument.body;
	
	if (obj.getBoundingClientRect && !ref) { /* pro IE a absolutni zjisteni se da pouzit tenhle trik od eltona: */
		var de = document.documentElement;
		var box = obj.getBoundingClientRect();
		var scroll = JAK.DOM.getBoxScroll(obj);
		return {left:box.left+scroll.x-de.clientLeft, top:box.top+scroll.y-de.clientTop};
	}

	while (obj && obj != refBox) {
		top += obj.offsetTop;
		left += obj.offsetLeft;

		/*pro FF2, safari a chrome, pokud narazime na fixed element, musime se u nej zastavit a pripocitat odscrolovani, ostatni prohlizece to delaji sami*/
		if ((JAK.Browser.client == 'gecko' && JAK.Browser.version < 3) || JAK.Browser.client == 'safari') {
			if (JAK.DOM.getStyle(obj, 'position') == 'fixed') {
				var scroll = JAK.DOM.getScrollPos();
				top += scroll.y;
				left += scroll.x;
				break;
			}
		}

		obj = obj.offsetParent;
	}
	return {top:top,left:left};
}

/*
	Par noticek k výpočtům odscrollovaní:
	- rodič body je html (documentElement), rodič html je document
	- v strict mode má scroll okna nastavené html
	- v quirks mode má scroll okna nastavené body
	- opera dává vždy do obou dvou
	- safari dává vždy jen do body
*/

/**
 * vrací polohu "obj" v okně nebo uvnitř objektu který předáme jako druhý 
 * argument, zahrnuje i potencialni odskrolovani kdekoliv nad objektem 
 *	Par noticek k výpočtům odscrollovaní:<ul>
 *	<li>rodič body je html (documentElement), rodič html je document</li>
 *	<li>v strict mode má scroll okna nastavené html</li>
 *	<li>v quirks mode má scroll okna nastavené body</li>
 *	<li>opera dává vždy do obou dvou</li>
 *	<li>safari dává vždy jen do body </li></ul>
 * @param {object} obj HTML elmenet, jehož pozici chci zjistit
 * @param {object} parent <strong>volitelný</strong> HTML element, vůči kterému chci zjistit pozici <em>obj</em>, element musí být jeho rodič
 * @param {bool} fixed <strong>volitelný</strong> flag, má-li se brát ohled na "fixed" prvky
 * @returns {object} s vlastnostmi :
 * <ul><li><em>left</em>(px) - horizontalní pozice prvku</li><li><em>top</em>(px) - vertikální pozice prvku</li></ul> 
 */
 JAK.DOM.getPortBoxPosition = function(obj, parent, fixed) {
	var pos = JAK.DOM.getBoxPosition(obj, parent, fixed);
	var scroll = JAK.DOM.getBoxScroll(obj, parent, fixed);
	pos.left -= scroll.x;
	pos.top -= scroll.y;
	return {left:pos.left,top:pos.top};
}

/**
 * vrací dvojici čísel, o kolik je "obj" odscrollovaný vůči oknu nebo vůči zadanému rodičovskému objektu
 * @param {object} obj HTML elmenet, jehož odskrolovaní chci zjistit
 * @param {object} ref <strong>volitelný</strong> HTML element, vůči kterému chci zjistit odskrolování <em>obj</em>, element musí být jeho rodič
 * @param {bool} fixed <strong>volitelný</strong> flag, má-li se brát ohled na "fixed" prvky
 * @returns {object} s vlastnostmi :
 * <ul><li><em>left</em>(px) - horizontální scroll prvku</li><li><em>top</em>(px) - vertikální scroll prvku</li></ul> 
 */
JAK.DOM.getBoxScroll = function(obj, ref, fixed) {
	var x = 0;
	var y = 0;
	var cur = obj.parentNode;
	var limit = ref || obj.ownerDocument.documentElement;
	var fix = false;
	while (1) {
		/* opera debil obcas nastavi scrollTop = offsetTop, aniz by bylo odscrollovano */
		if (JAK.Browser.client == "opera" && JAK.DOM.getStyle(cur,"display") != "block") { 
			cur = cur.parentNode;
			continue; 
		}
		
		/* a taky stara opera (<9.5) pocita scrollTop jak pro <body>, tak pro <html> - takze <body> preskocime */
		if (JAK.Browser.client == "opera" && JAK.Browser.version < 9.5 && cur == document.body) { 
			cur = cur.parentNode;
			continue; 
		}
		
		if (fixed && JAK.DOM.getStyle(cur, "position") == "fixed") { fix = true; }
		
		if (!fix) {
			x += cur.scrollLeft;
			y += cur.scrollTop;
		}
		
		if (cur == limit) { break; }
		cur = cur.parentNode;
		if (!cur) { break; }
	}
	return {x:x,y:y};
}

/**
 * vrací aktuální odskrolování stránky
 * @returns {object} s vlastnostmi:
 * <ul><li><em>x</em>(px) - horizontální odskrolování</li><li><em>y</em>(px) - vertikální odskrolování</li></ul> 
 */
JAK.DOM.getScrollPos = function(){
	if (document.documentElement.scrollTop || document.documentElement.scrollLeft) {
		var ox = document.documentElement.scrollLeft;
		var oy = document.documentElement.scrollTop;
	} else if (document.body.scrollTop || document.body.scrollLeft) { 
		var ox = document.body.scrollLeft;
		var oy = document.body.scrollTop;
	} else {
		var ox = 0;
		var oy = 0;
	}
	return {x:ox,y:oy};
}

/**
 * vraci současnou hodnotu nějaké css vlastnosti
 * @param {object} elm HTML elmenet, jehož vlasnost nás zajímá
 * @param {string} property řetězec s názvem vlastnosti ("border","backgroundColor",...)
 */
JAK.DOM.getStyle = function(elm, property) {
	if (document.defaultView && document.defaultView.getComputedStyle) {
		var cs = elm.ownerDocument.defaultView.getComputedStyle(elm,'');
		if (!cs) { return false; }
		return cs[property];
	} else {
		return elm.currentStyle[property];
	}
}

/**
 * nastavuje objektu konkretni styly, ktere jsou zadany v objektu pojmenovanych vlastnosti (nazev_CSS : hodnota)
 * @param {object} elm HTML element, jehož vlastnosti měním
 * @param {object} style objekt nastavovaných vlastností, např.: {color: 'red', backgroundColor: 'white'}
 */
JAK.DOM.setStyle = function(elm, style) {
	for (var name in style) {
		elm.style[name] = style[name];
	}
}

/**
 * Přidá do dokumentu zadaný CSS řetězec
 * @param {string} css Kus CSS deklarací
 * @returns {node} vyrobený prvek
 */
JAK.DOM.writeStyle = function(css) {
	var node = JAK.mel("style", {type:"text/css"});
	if (node.styleSheet) { /* ie */
		node.styleSheet.cssText = css;
	} else { /* non-ie */
		node.appendChild(JAK.ctext(css));
	}
	var head = document.getElementsByTagName("head");
	if (head.length) {
		head = head[0];
	} else {
		head = JAK.cel("head");
		document.documentElement.appendChild(head, document.body);
	}
	head.appendChild(node);
	return node;
}

/**
 * skrývá elementy které se mohou objevit v nejvyšší vrstvě a překrýt obsah,
 * resp. nelze je překrýt dalším obsahem (typicky &lt;SELECT&gt; v internet exploreru)
 * @param {object | string} HTML element nebo jeho ID pod kterým chceme skrývat problematické prvky
 * @param {array} elements pole obsahující názvy problematických elementů
 * @param {string} action akce kterou chceme provést 'hide' pro skrytí 'show' nebo cokoli jiného než hide pro zobrazení
 * @examples 
 *  <pre>
 * JAK.DOM.elementsHider(JAK.gel('test'),['select'],'hide')
 * JAK.DOM.elementsHider(JAK.gel('test'),['select'],'show')
 *</pre>   									
 *
 */     
JAK.DOM.elementsHider = function(obj, elements, action) {
	var elems = elements;
	if (!elems) { elems = ["select","object","embed","iframe"]; }
	
	/* nejprve zobrazit vsechny jiz skryte */
	var hidden = arguments.callee.hidden;
	if (hidden) {
		hidden.forEach(function(node){
			node.style.visibility = "visible";
		});
		arguments.callee.hidden = [];
	}
	
	function verifyParent(node) {
		var ok = false;
		var cur = node;
		while (cur.parentNode && cur != document) {
			if (cur == obj) { ok = true; }
			cur = cur.parentNode;
		}
		return ok;
	}
	
	if (action == "hide") { /* budeme schovavat */
		if (typeof obj == 'string') { obj = JAK.gel(obj); }
		var hidden = [];
		var box = this.getBoxPosition(obj);
		
		box.width =  obj.offsetWidth + box.left;
		box.height = obj.offsetHeight +box.top;	
		for (var e = 0; e < elems.length; ++e) { /* pro kazdy typ uzlu */
			var elm = document.getElementsByTagName(elems[e]);
			for (var f = 0; f < elm.length; ++f) { /* vsechny uzly daneho typu */
				var node = this.getBoxPosition(elm[f]);
				if (verifyParent(elm[f])) { continue; } /* pokud jsou v kontejneru, pod kterym schovavame, tak fakof */
				node.width = elm[f].offsetWidth + node.left;
				node.height = elm[f].offsetHeight + node.top;
				
				if (!((box.left> node.width) || (box.width < node.left) || (box.top > node.height) || (box.height < node.top))) {
					elm[f].style.visibility = 'hidden';
					hidden.push(elm[f]);
				}
			}
		}
		arguments.callee.hidden = hidden;
	}
}

/**
 * Vrátí kolekci elementů, které mají nadefinovanou třídu <em>searchClass</em>
 * @param {string} searchClass vyhledávaná třída
 * @param {object} node element dokumentu, ve kterém se má hledat, je-li null prohledává
 * se celý dokument 
 * @param {string} tag název tagu na který se má hledání omezit, je-li null prohledávají se všechny elementy
 * @returns {array} pole které obsahuje všechny nalezené elementy, které mají definovanou třídu <em>searchClass</em>
 */      
JAK.DOM.getElementsByClass = function(searchClass,node,tag) {
	if (document.getElementsByClassName && !tag) { /* kde lze, uplatnime nativni metodu */
		var elm = node || document;
		return JAK.DOM.arrayFromCollection(elm.getElementsByClassName(searchClass));
	}

	if (document.querySelectorAll && !tag) { /* kde lze, uplatnime nativni metodu */
		var elm = node || document;
		return JAK.DOM.arrayFromCollection(elm.querySelectorAll("."+searchClass));
	}

	var classElements = [];
	var node = node || document;
	var tag = tag || "*";

	var els = node.getElementsByTagName(tag);
	var elsLen = els.length;
	
	var pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)");
	for (var i = 0, j = 0; i < elsLen; i++) {
		if (pattern.test(els[i].className)) {
			classElements[j] = els[i];
			j++;
		}
	}
	return classElements;
}

/**
 * Převede html kolekci, kterou vrací např. document.getElementsByTagName, na pole, které lze
 * lépe procházet a není "živé" (z které se při procházení můžou ztrácet prvky zásahem jiného skriptu)
 * @param {HTMLCollection} col
 * @return {array}   
 */ 
JAK.DOM.arrayFromCollection = function(col) {
	var result = [];
	try {
		result = Array.prototype.slice.call(col);
	} catch(e) {
		for (var i=0;i<col.length;i++) { result.push(col[i]); }
	} finally {
		return result;
	}
}

/**
 * Rozdělí kus HTML kódu na ne-javascriptovou a javascriptovou část. Chceme-li pak
 * simulovat vykonání kódu prohlížečem, první část vyinnerHTMLíme a druhou vyevalíme.
 * @param {string} str HTML kód
 * @returns {string[]} pole se dvěma položkami - čistým HTML a čistým JS
 */
JAK.DOM.separateCode = function(str) {
    var js = [];
    var out = {}
    var s = str.replace(/<script.*?>([\s\S]*?)<\/script>/g, function(tag, code) {
        js.push(code);
        return "";
    });
    return [s, js.join("\n")];
}

/**
 * Spočítá, o kolik je nutno posunout prvek tak, aby byl vidět v průhledu.
 * @param {node} box
 * @returns {int[]}
 */
JAK.DOM.shiftBox = function(box) {
	var dx = 0;
	var dy = 0;
	
	/* soucasne souradnice vuci pruhledu */
	var pos = JAK.DOM.getBoxPosition(box);
	var scroll = JAK.DOM.getScrollPos();
	pos.left -= scroll.x;
	pos.top -= scroll.y;
	
	var port = JAK.DOM.getDocSize();
	var w = box.offsetWidth;
	var h = box.offsetHeight;
	
	/* dolni okraj */
	var diff = pos.top + h - port.height;
	if (diff > 0) {
		pos.top -= diff;
		dy -= diff;
	}

	/* pravy okraj */
	var diff = pos.left + w - port.width;
	if (diff > 0) {
		pos.left -= diff;
		dx -= diff;
	}
	
	/* horni okraj */
	var diff = pos.top;
	if (diff < 0) {
		pos.top -= diff;
		dy -= diff;
	}

	/* levy okraj */
	var diff = pos.left;
	if (diff < 0) {
		pos.left -= diff;
		dx -= diff;
	}
	
	return [dx, dy];
}

/**
 * Zjistí jakou šířku má scrollbar v použitém prohlížeci/grafickém prostředí
 * @returns {int}
 */ 
JAK.DOM.scrollbarWidth = function() {
    var div = JAK.mel('div', false, {width: '50px', height: '50px', overflow: 'hidden', position: 'absolute', left: '-200px'});
    var innerDiv = JAK.mel('div', false, {height: '100px'});
    div.appendChild(innerDiv);
    // Append our div, do our calculation and then remove it
    document.body.insertBefore(div, document.body.firstChild);
    var w1 = div.clientWidth + parseInt(JAK.DOM.getStyle(div,'paddingLeft')) + parseInt(JAK.DOM.getStyle(div,'paddingRight'));
    JAK.DOM.setStyle(div, {overflowY: 'scroll'});
    var w2 = div.clientWidth + parseInt(JAK.DOM.getStyle(div,'paddingLeft')) + parseInt(JAK.DOM.getStyle(div,'paddingRight'));
    document.body.removeChild(div);

    return (w1 - w2);
}

/**
 * Vrátí rodiče zadaného uzlu, vyhovujícího CSS selektoru
 * @param {node} node
 * @param {string} selector
 */
JAK.DOM.findParent = function(node, selector) {
	/* pokud je prazdny nebo nezadany, dostaneme prazdne pole omezujicich podminek -- a vratime prvniho rodice */
	var parts = (selector || "").match(/[#.]?[a-z0-9]+/ig) || [];
	
	var n = node.parentNode;
	while (n && n != document) {
		var ok = true;
		for (var i=0;i<parts.length;i++) {
			var part = parts[i];
			switch (part.charAt(0)) {
				case "#":
					if (n.id != part.substring(1)) { ok = false; }
				break;
				case ".":
					if (!JAK.DOM.hasClass(n, part.substring(1))) { ok = false; }
				break;
				default:
					if (n.nodeName.toLowerCase() != part.toLowerCase()) { ok = false; }
				break;
			}
		}
		if (ok) { return n; }
		n = n.parentNode;
	}
	return null;
}
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @class Třída provádí operace s objekty jako je jejich porovnávaní a serializace a deserializace. Obsolete!
 * @group jak
 */    
JAK.ObjLib = JAK.ClassMaker.makeClass({
	NAME: "ObjLib",
	VERSION: "3.1"
});

JAK.ObjLib.prototype.reSetOptions = function() {
}

JAK.ObjLib.prototype.pretty = function(str) {
	return str;
}

JAK.ObjLib.prototype.serialize = function(objToSource) {
	return JSON.stringify(objToSource);
};

JAK.ObjLib.prototype.unserialize = function(serializedString) {
	return JSON.parse(serializedString);
}

JAK.ObjLib.prototype.match = function(refObj, matchObj){
	return (JSON.stringify(refObj) == JSON.stringify(matchObj));
};

JAK.ObjLib.prototype.copy = function(objToCopy) {
	return JSON.parse(JSON.stringify(objToCopy));
};

JAK.ObjLib.prototype.arrayCopy = function(arrayToCopy) {
	return this.copy(arrayToCopy);
};
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @class XML/TEXT/JSONP request
 * @group jak
 * @example
 * var r = new JAK.Request(JAK.Request.XML, {method:"get"});
 * r.setCallback(mujObjekt, "jehoMetoda");
 * r.send("/dobrerano", {a:b, c:"asdf&asdf"});
 */
JAK.Request = JAK.ClassMaker.makeClass({
	NAME: "JAK.Request",
	VERSION: "2.0"
});

/** @constant */
JAK.Request.XML		= 0;
/** @constant */
JAK.Request.TEXT	= 1;
/** @constant */
JAK.Request.JSONP	= 2;
/** @constant */
JAK.Request.BINARY	= 3;

/**
 * Podporuje tento prohlizec CORS?
 */
JAK.Request.supportsCrossOrigin = function() {
	if (JAK.Browser.client == "opera") { return false; }
	if (JAK.Browser.client == "ie" && JAK.Browser.version < 8) { return false; }
	if (JAK.Browser.client == "gecko" && parseFloat(JAK.Browser.version) < 3.5) { return false; }
	return true;
}

/**
 * @param {int} type Type požadavku, jedna z konstant JAK.Request.*
 * @param {object} [options] Konfigurační objekt
 * @param {bool} [options.async=true] Je-li požadavek asynchronní
 * @param {bool} [options.timeout=0] Timeout v msec; 0 = disable
 * @param {bool} [options.method="get"] HTTP metoda požadavku
 */
JAK.Request.prototype.$constructor = function(type, options) {
	this._NEW		= 0;
	this._SENT		= 1;
	this._DONE		= 2;
	this._ABORTED	= 3;
	this._TIMEOUT	= 4;
	
	this._xhr = null;
	this._callback = "";
	this._script = null;
	this._type = type;
	this._headers = {};
	this._callbacks = {};
	this._state = this._NEW;
	this._xdomain = false; /* pouzivame IE8+ XDomainRequest? */
	
	this._options = {
		async: true,
		timeout: 0,
		method: "get"
	}
	for (var p in options) { this._options[p] = options[p]; }

	if (this._type == JAK.Request.JSONP) {
		if (this._options.method.toLowerCase() == "post") { throw new Error("POST not supported in JSONP mode"); }
		if (!this._options.async) { throw new Error("Async not supported in JSONP mode"); }
	}
};

JAK.Request.prototype.$destructor = function() {
	if (this._state == this._SENT) { this.abort(); }
	this._xhr = null;
}

/**
 * Nastaví hlavičky požadavku
 * @param {object} headers Hlavičky (dvojice název:hodnota)
 */
JAK.Request.prototype.setHeaders = function(headers) {
	if (this._type == JAK.Request.JSONP) { throw new Error("Request headers not supported in JSONP mode"); }
	for (var p in headers) { this._headers[p] = headers[p]; }
}

/**
 * Vrátí hlavičky odpovědi
 * @returns {object} Hlavičky (dvojice název:hodnota)
 */
JAK.Request.prototype.getHeaders = function() {
	if (this._state != this._DONE) { throw new Error("Response headers not available"); }
	if (this._type == JAK.Request.JSONP) { 	throw new Error("Response headers not supported in JSONP mode"); }
	var headers = {};
	var h = this._xhr.getAllResponseHeaders();
	if (h) {
		h = h.split(/[\r\n]/);
		for (var i=0;i<h.length;i++) if (h[i]) {
			var v = h[i].match(/^([^:]+): *(.*)$/);
			headers[v[1]] = v[2];
		}
	}
	return headers;
}

/**
 * Odešle požadavek
 * @param {string} url Cílové URL
 * @param {string || object} [data] Data k odeslání
 */
JAK.Request.prototype.send = function(url, data) {
	if (this._state != this._NEW) { throw new Error("Request already sent"); }

	this._state = this._SENT;
	this._userCallback(this);

	switch (this._type) {
		case JAK.Request.XML:
		case JAK.Request.TEXT:
		case JAK.Request.BINARY:
			return this._sendXHR(url, data);
		break;
		case JAK.Request.JSONP:
			return this._sendScript(url, data);
		break;
		default:
			throw new Error("Unknown request type");
		break;
	}
}

/**
 * Přeruší probíhající požadavek
 * @returns {bool} Byl požadavek přerušen?
 */
JAK.Request.prototype.abort = function() {
	if (this._state != this._SENT) { return false; }
	this._state = this._ABORTED;
	if (this._xhr) { this._xhr.abort(); }
	this._userCallback(this);
	return true;
}

/**
 * Nastavení callbacku po dokončení požadavku
 * @param {object || null} obj
 * @param {function || string} method
 */
JAK.Request.prototype.setCallback = function(obj, method) {
	this._setCallback(obj, method, this._DONE);
	return this;
}

/**
 * Nastavení callbacku po odeslání
 * @see JAK.Request#setCallback
 */
JAK.Request.prototype.setSendCallback = function(obj, method) {
	this._setCallback(obj, method, this._SENT);
	return this;
}

/**
 * Nastavení callbacku po abortu
 * @see JAK.Request#setCallback
 */
JAK.Request.prototype.setAbortCallback = function(obj, method) {
	this._setCallback(obj, method, this._ABORTED);
	return this;
}

/**
 * Nastavení callbacku po timeoutu
 * @see JAK.Request#setCallback
 */
JAK.Request.prototype.setTimeoutCallback = function(obj, method) {
	this._setCallback(obj, method, this._TIMEOUT);
	return this;
}

/**
 * Interni registrace callbacku pro zadany stav
 */
JAK.Request.prototype._setCallback = function(obj, method, state) {
	this._callbacks[state] = [obj, method];
}

/**
 * Odeslani pozadavku pres XHR
 */
JAK.Request.prototype._sendXHR = function(url, data) {
	/* nejprve vyrobit instanci XHR */
	if (window.XMLHttpRequest) { 
		var ctor = XMLHttpRequest;
		var r = url.match(/^https?\:\/\/(.*?)\//);
		if (r && r[1] != location.host && window.XDomainRequest) { /* pro cross-domain je v IE >= 8 novy objekt */
			if (this._type == JAK.Request.BINARY) { throw new Error("XDomainRequest does not support BINARY mode"); }
			this._xdomain = true;
			ctor = XDomainRequest; 
		}
		this._xhr = new ctor(); 
	} else if (window.ActiveXObject) { 
		this._xhr = new ActiveXObject("Microsoft.XMLHTTP"); 
	} else { throw new Error("No XHR available"); }
	
	if (this._xdomain) {
		this._xhr.onload = this._onXDomainRequestLoad.bind(this);
	} else {
		this._xhr.onreadystatechange = this._onReadyStateChange.bind(this);
	}

	/* zpracovat parametry */
	var u, d;
	if (this._options.method.toLowerCase() == "get") {
		u = this._buildURL(url, data);
		d = null;
	} else {
		u = url;
		d = this._serializeData(data);
		
		var ctSet = false;
		for (var p in this._headers) {
			if (p.toLowerCase() == "content-type") { 
				ctSet = true;
				break;
			}
		}
		if (!ctSet) { this.setHeaders({"Content-Type":"application/x-www-form-urlencoded"}); }
	}

	if (this._type == JAK.Request.BINARY) {
		if (this._xhr.overrideMimeType) {
			this._xhr.overrideMimeType("text/plain; charset=x-user-defined");
		} else if (JAK.Browser.client == "ie") {
			this._buildVBS();
		} else {
			throw new Error("This browser does not support binary transfer");
		}
	}

	this._xhr.open(this._options.method, u, this._options.async);
	for (var p in this._headers) { this._xhr.setRequestHeader(p, this._headers[p]); }
	this._xhr.send(d);
	
	if (this._options.timeout) { setTimeout(this._timeout.bind(this), this._options.timeout); }
	if (!this._options.async) { this._onReadyStateChange(); }
	
	return u;
}

/**
 * Odeslani JSONP pozadavku pres &lt;script&gt;
 */
JAK.Request.prototype._sendScript = function(url, data) {
	var o = data || {};

	this._callback = "callback" + JAK.idGenerator();
	o.callback = this._callback;
	var url = this._buildURL(url, o);
	window[this._callback] = this._scriptCallback.bind(this);
	
	this._script = JAK.mel("script", {type:"text/javascript", src:url});
	document.body.insertBefore(this._script, document.body.firstChild);

	return url;
}

/**
 * Tvorba URL zmixovanim zakladu + dat
 */
JAK.Request.prototype._buildURL = function(url, data) {
	var s = this._serializeData(data);
	if (!s.length) { return url; }
	
	if (url.indexOf("?") == -1) {
		return url + "?" + s;
	} else {
		return url + "&" + s;
	}
}

/**
 * Serialize dat podle HTML formularu
 */
JAK.Request.prototype._serializeData = function(data) {
	if (typeof(data) == "string") { return data; }
	if (window.File && data instanceof File) { return data; }
	if (!data) { return ""; }
	
	var arr = [];
	for (var p in data) {
		var value = data[p];
		if (!(value instanceof Array)) { value = [value]; }
		for (var i=0;i<value.length;i++) {
			arr.push(encodeURIComponent(p) + "=" + encodeURIComponent(value[i]));
		}
	}
	return arr.join("&");
}

/**
 * Zmena stavu XHR
 */
JAK.Request.prototype._onReadyStateChange = function() {
	if (this._state == this._ABORTED) { return; }
	if (this._xhr.readyState != 4) { return; }

	var status = this._xhr.status;
	var data;

	if (this._type == JAK.Request.BINARY) {
		data = [];
		if (JAK.Browser.client == "ie") {
			var length = VBS_getLength(this._xhr.responseBody);
			for (var i=0;i<length;i++) { data.push(VBS_getByte(this._xhr.responseBody, i)); }
		} else {
			var text = this._xhr.responseText;
			var length = text.length;
			for (var i=0;i<length;i++) { data.push(text.charCodeAt(i) & 0xFF); }
		}
	} else {
		data = (this._type == JAK.Request.XML ? this._xhr.responseXML : this._xhr.responseText);
	}

	this._done(data, status);
}

/**
 * Nacteni XDomainRequestu
 */
JAK.Request.prototype._onXDomainRequestLoad = function() {
	if (this._state == this._ABORTED) { return; }

	var data = this._xhr.responseText;
	if (this._type == JAK.Request.XML) {
		var xml = new ActiveXObject("Microsoft.XMLDOM");
		xml.async = false;
		xml.loadXML(data);
		data = xml;
	}

	this._done(data, 200);
}

/**
 * JSONP callback
 */
JAK.Request.prototype._scriptCallback = function(data) {
	this._script.parentNode.removeChild(this._script);
	this._script = null;
	delete window[this._callback];

	if (this._state != this._ABORTED) { this._done(data, 200); }
}

/**
 * Request uspesne dokoncen
 */
JAK.Request.prototype._done = function(data, status) {
	if (this._state == this._DONE) { return; }
	this._state = this._DONE;
	this._userCallback(data, status, this);
}

/**
 * Nastal timeout
 */
JAK.Request.prototype._timeout = function() {
	if (this._state != this._SENT) { return; }
	this.abort();
	
	this._state = this._TIMEOUT;
	this._userCallback(this);	
}

/**
 * Volani uziv. callbacku
 */
JAK.Request.prototype._userCallback = function() {
	var data = this._callbacks[this._state];
	if (!data) { return; }
	
	var obj = data[0] || window;
	var method = data[1];
	
	if (obj && typeof(method) == "string") { method = obj[method]; }
	if (!method) {
		method = obj;
		obj = window;
	}
	
	method.apply(obj, arguments);
}

JAK.Request.prototype._buildVBS = function() {
	var s = JAK.mel("script", {type:"text/vbscript"});
	s.text = "Function VBS_getByte(data, pos)\n"
		+ "VBS_getByte = AscB(MidB(data, pos+1,1))\n"
		+ "End Function\n"
		+ "Function VBS_getLength(data)\n"
		+ "VBS_getLength = LenB(data)\n"
		+ "End Function";
	document.getElementsByTagName("head")[0].appendChild(s);
}
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @overview Vytváření a zachytávání vlastních uživatelských událostí
 * @version 2.1
 * @author jelc, zara
 */
 
/**
 * @class Třída pro práci s uživatelsky definovanými událostmi
 * @group jak
 */
JAK.Signals = JAK.ClassMaker.makeClass({
	NAME: "JAK.Signals",
	VERSION: "2.1"
});
 
JAK.Signals.prototype.$constructor = function() {
	/**
	 * @field {object} zásobník posluchačů událostí
	 */
	this._myHandleFolder = {};
	
	/**
	 * @field {object} pomocný IDčkový index pro rychlé odebírání - pro ID obsahuje pole typových zásobníků
	 */
	this._myIdFolder = {};
};

/**
 * registrace posluchače uživatelské události, pokud je již na stejný druh 
 * události zaregistrována shodná metoda shodného objektu nic se neprovede,
 * @param {object} owner objekt/třída,  která naslouchá, a v jehož oboru platnosti se zpracovaní události provede
 * @param {string} type	typ události, kterou chceme zachytit; možno zadat víc názvů naráz oddělených mezerami 
 * @param {string} functionName funkce/metoda posluchače, která má danou událost zpracovat
 * @param {object} sender objekt, jehož událost chceme poslouchat. Pokud není zadáno (nebo false), odesilatele nerozlišujeme
 * @returns {id} id události / null
 */
JAK.Signals.prototype.addListener = function(owner, type, funcOrString, sender){
	var newId = JAK.idGenerator(); /* identifikátor handlované události */
	var typeFolders = [];

	var data = {
		eOwner		: owner,
		eFunction	: funcOrString,
		eSender		: sender
	};
	
	var types = type.split(" ");
	for (var i=0;i<types.length;i++) {
		var t = types[i];
		
		if (!(t in this._myHandleFolder)) { /* zasobnik pro dany typ udalosti neexistuje musim ho vytvorit */
			this._myHandleFolder[t] = {};
		} 
		
		var typeFolder = this._myHandleFolder[t]; /* sem ukladam zaznam - vsichni poslouchajici na dany signal */
		
		var ok = true; /* test duplicitniho zaveseni */
		for (var id in typeFolder) { 
			var item = typeFolder[id];
			if (
				(item.eFunction == funcOrString) && 
				(item.eOwner == owner) &&
				(item.eSender == sender)
			) {
				ok = false;
			}
		}
		if (!ok) { continue; }

		/* konecne si to můžu zaregistrovat */
		typeFolder[newId] = data;
		typeFolders.push(typeFolder);
	}
	
	if (typeFolders.length) { /* jeste pridam do ID zasobniku */
		this._myIdFolder[newId] = typeFolders;
		return newId;
	} else {
		return null;
	}
};


/**
 * Odstranění naslouchání události.
 * @param {id} id ID události
 */
JAK.Signals.prototype.removeListener = function(id) {
	var typeFolders = this._myIdFolder[id];
	if (!typeFolders) { throw new Error("Cannot remove non-existent signal ID '"+id+"'"); }
	
	while (typeFolders.length) {
		var typeFolder = typeFolders.pop();
		delete typeFolder[id];
	}

	delete this._myIdFolder[id];
};

/**
 * provede odvěšení signálů podle jejich <em>id</em> uložených v poli
 * @param {array} array pole ID signálu jak je vrací metoda <em>addListener</em>
 */  
JAK.Signals.prototype.removeListeners = function(array) {
	while (array.length) {
		this.removeListener(array.shift());
	}
};

/**
 * vytváří událost, ukládá ji do zásobníku události a předává ji ke zpracování
 * @param {string} type název nové události
 * @param {object} trg reference na objekt, který událost vyvolal
 * @param {object} [data] objekt s vlastnostmi specifickými pro danou událost 
 */   
JAK.Signals.prototype.makeEvent = function(type, trg, data) {
	var event = {
		type: type,
		target: trg,
		timeStamp: new Date().getTime(),
		data: (data && typeof data == 'object') ? data : null
	}
	this._myEventHandler(event);
};

/**
 * zpracuje událost - spustí metodu, která byla zaragistrována jako posluchač  
 * @param {object} myEvent zpracovávaná událost
 */    
JAK.Signals.prototype._myEventHandler = function(myEvent) {
	var functionCache = [];

	for (var type in this._myHandleFolder){
		if (type == myEvent.type || type == "*") { /* shoda nazvu udalosti */
			for (var p in this._myHandleFolder[type]) {
				var item = this._myHandleFolder[type][p];
				if (!item.eSender || item.eSender == myEvent.target) {
					functionCache.push(item);
				}
			}
		}
	}
	
	for (var i=0;i<functionCache.length;i++) {
		var item = functionCache[i];
		var owner = item.eOwner;
		var fnc = item.eFunction;
		if(typeof fnc == 'string'){
			owner[fnc](myEvent);
		} else if(typeof fnc == 'function'){
			fnc(myEvent);
		}
	}
};

/**
 * Výchozí instance
 */
JAK.signals = new JAK.Signals();
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @overview Rozhraní určené k práci s uživatelskými událostmi a "globálními" 
 * zprávami, které zjednodušuje práci s objektem, který se o uživatelsky 
 * definované události stará
 * @version 2.1
 * @author jelc, zara
 */   

/**
 * @class Rozhraní pro práci s uživatelsky definovanými událostmi a zprávami
 * vyžaduje referenci na instanci třídy JAK.signals, všechny následující metody
 * jsou určeny k použití pouze jako zděděné vlastnosti rozhraní,
 * @group jak
 * @see JAK.Signals
 */  
JAK.ISignals = JAK.ClassMaker.makeInterface({
	NAME: "JAK.ISignals",
	VERSION: "2.1"
});

/**
 * slouží k nalezení rozhraní u rodičovských tříd, hledá v nadřazených třídách třídu,
 * ktera ma nastavenou vlastnost TOP_LEVEL a v ni očekává instanci třídy JAK.Signals s
 * nazvem "interfaceName"
 * @param {string}	interfaceName  název instance třídy JAK.Signals v daném objektu 
 * @returns {object} referenci na instanci třídy JAK.Signals
 * @throws {error} 	SetInterface:Interface not found  
 */
JAK.ISignals.prototype.setInterface = function(interfaceName) {
	if (typeof(this[interfaceName]) != 'object') {
		var owner = this._owner;
		while(typeof(owner[interfaceName])== 'undefined'){
			if(typeof owner.TOP_LEVEL != 'undefined'){
				throw new Error('SetInterface:Interface not found');
			} else {
				owner = owner._owner;
			}
		}
		return owner[interfaceName];
	} 
};

/**
 * slouží k registraci zachytávaní události nad objektem, který implementuje toto rozhraní
 * @param {string} type název události, kterou chceme zachytit
 * @param {string} handleFunction název metody objektu 'myListener', která bude zpracovávat událost
 * @param {object} sender objekt, jehož událost chceme poslouchat. Pokud není zadáno (nebo false), odesilatele nerozlišujeme
 * @returns {int} 1 v případě neúspěchu, 0 v pripade úspěchu  
 */
JAK.ISignals.prototype.addListener = function(type, handleFunction, sender) {
	return this.getInterface().addListener(this, type, handleFunction, sender);
};

/**
 * Slouží k zrušení zachytáváni události objektem, který implementuje toto rozhraní. 
 * @param {id} ID události, kterou jsme zachytávali
 */
JAK.ISignals.prototype.removeListener = function(id) {
	return this.getInterface().removeListener(id);
};

/**
 * Provede odvěšení signálů podle jejich <em>id</em> uložených v poli
 * @param {array} array pole ID signálu jak je vrací metoda <em>addListener</em>
 */  
JAK.ISignals.prototype.removeListeners = function(array) {
	this.getInterface().removeListeners(array);
}

/**
 * vytváří novou událost, kterou zachytáva instance třídy JAK.Signals
 * @param {string} type název vyvolané události
 * @param {object} [data] objekt s vlastnostmi specifickými pro danou událost  
 *					  nebo pouze vnitrnim objektum [private | public]
 * @throws {error} pokud neexistuje odkaz na instanci JAK.Signals vyvolá chybu 'Interface not defined'  
 */
JAK.ISignals.prototype.makeEvent = function(type, data) {
	this.getInterface().makeEvent(type, this, data);
};

JAK.ISignals.prototype.getInterface = function() {
	return (typeof(this.signals) == "object" ? this.signals : JAK.signals);
}
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @overview Základní nástroje pro práci s "dekorátory".
 * Úvozovky okolo názvu jsou na místě, neb nejde o realizaci návrhového vzoru,
 * ale o naše vlastní, monkeypatch-based řešení.
 * @version 2.0
 * @author zara
 */   

/**
 * @class Abstraktní dekorátor, jedináček
 * @group jak
 */
JAK.AbstractDecorator = JAK.ClassMaker.makeSingleton({
	NAME: "JAK.AbstractDecorator",
	VERSION: "2.0"
});

/**
 * Dekorační metoda
 * @param {object} instance To, co chceme poupravit
 * @returns {object} Vrací to, co obdrží
 */
JAK.AbstractDecorator.prototype.decorate = function(instance) {
	instance.$super = this._$super;
	if (!instance.__decorators) { instance.__decorators = []; }
	instance.__decorators.push(this);
	return instance;
}

/**
 * Metoda volání "předka", magie pro otrlé.
 * Volá stejně pojmenovanou metodu objektu před odekorováním. 
 * Pokud je voláno z neodekorované metody, chová se jako $super z ClassMakeru.
 */
JAK.AbstractDecorator.prototype._$super = function() {
	var caller = arguments.callee.caller;
	if (!caller) { throw new Error("Function.prototype.caller not supported"); }

	var decorators = this.__decorators || [];
	var obj = null; /* objekt, jehoz metodu chceme volat */
	var name = null; /* nazev metody */
	
	var i = decorators.length;
	while (i--) { /* projdu vsechny naaplikovane dekoratory */
		var d = decorators[i];
		/**
		 * Hledam dve veci:
		 *  - jak se jmenuje metoda, ze ktere je $super volan,
		 *  - kde je tato metoda deklarovana pred timto dekoratorem
		 */
		
		if (!obj && name && (name in d)) { obj = d; break; } /* mame predchozi objekt s metodou */
		
		for (var p in d) { /* hledame objekt s touto metodou a jeji nazev */
			if (!name && caller == d[p]) { name = p; break; }
		}
	}

	if (!name) {
		/** 
		 * Metoda, ze ktere je volan $super, neni definovana v zadnem dekoratoru.
		 * Chteme tedy volat normalne metodu predka - kod je vybrakovan z ClassMakeru (_$super).
		 */
		var owner = caller.owner || this.constructor; /* toto je trida, kde jsme "ted" */

		var callerName = false;
		for (var name in owner.prototype) {
			if (owner.prototype[name] == caller) { callerName = name; }
		}
		if (!callerName) { throw new Error("Cannot find supplied method in constructor"); }
		
		var parent = owner.EXTEND;
		if (!parent) { throw new Error("No super-class available"); }
		if (!parent.prototype[callerName]) { throw new Error("Super-class doesn't have method '"+callerName+"'"); }

		var func = parent.prototype[callerName];
		return func.apply(this, arguments);
		
	} else if (!obj) {
		/**
		 * Predchudcem teto metody je primo prototypova metoda instance
		 */
		obj = this.constructor.prototype;
		if (!(name in obj)) { throw new Error("Function '"+name+"' has no undecorated parent"); }
	}
	
	return obj[name].apply(this, arguments);
}

/**
 * @class Automatický dekorátor - předá instanci veškeré své metody
 * @augments JAK.AbstractDecorator
 */
JAK.AutoDecorator = JAK.ClassMaker.makeSingleton({
	NAME: "JAK.AutoDecorator",
	VERSION: "1.0",
	EXTEND: JAK.AbstractDecorator
});

/**
 * @see JAK.AbstractDecorator#decorate
 */
JAK.AutoDecorator.prototype.decorate = function(instance) {
	this.$super(instance);
	var exclude = ["constructor", "$super", "_$super", "decorate"];
	
	for (var p in this) {
		if (exclude.indexOf(p) != -1) { continue; }
		instance[p] = this[p];
	}
}
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @class Dekorační rozhraní; implementuje ho ten, kdo chce být dekorován
 * @group jak
 */
JAK.IDecorable = JAK.ClassMaker.makeClass({
	NAME: "JAK.IDecorable",
	VERSION: "2.0",
	CLASS: "class"
});

/**
 * Odekorování této instance zadaným dekorátorem
 * @param {function} decorator Konstruktor dekorátoru
 * @returns {object} Vrací this
 */
JAK.IDecorable.prototype.decorate = function(decorator) {
	var args = [this];
	for (var i=1;i<arguments.length;i++) { args.push(arguments[i]); }
	var dec = decorator.getInstance();
	return dec.decorate.apply(dec, args);
}
/*
	Licencováno pod MIT Licencí, její celý text je uveden v souboru licence.txt
	Licenced under the MIT Licence, complete text is available in licence.txt file
*/

/**
 * @class Metronom: udržuje běžící interval (default 60fps nebo requestAnimationFrame) a notifikuje o jeho průběhu všechny zájemce
 * @group jak-utils
 */
JAK.Timekeeper = JAK.ClassMaker.makeSingleton({
	NAME: "JAK.Timekeeper",
	VERSION: "1.1"
});

JAK.Timekeeper.prototype.$constructor = function() {
	this._listeners = [];
	this._running = 0; /* 0 = stopped, 1 = stopping, 2 = running */
	this._tick = this._tick.bind(this);

	this._scheduler = window.requestAnimationFrame 
						|| window.webkitRequestAnimationFrame 
						|| window.mozRequestAnimationFrame 
						|| window.oRequestAnimationFrame 
						|| window.msRequestAnimationFrame 
						|| function(callback, element) {
              				setTimeout(callback, 1000/60);
           				};
}

/**
 * Přidání posluchače
 * @param {object} what Objekt žádající o notifikaci
 * @param {string || function} method Metoda k volání
 * @param {int} [count=1] Počet tiknutí na jednu notifikaci
 */
JAK.Timekeeper.prototype.addListener = function(what, method, count) {
	var index = this._findListener(what);
	if (index != -1) { throw new Error("This listener is already attached"); }
	
	var obj = {
		what: what,
		method: method,
		count: count || 1,
		bucket: 0
	}
	obj.bucket = obj.count;
	this._listeners.push(obj);
	
	if (this._running != 2) { 
		if (this._running == 0) { this._schedule(); }
		this._running = 2;
	}
	return this;
}

/**
 * Odebrání posluchače
 * @param {object} what Objekt žádající o odebrání
 */
JAK.Timekeeper.prototype.removeListener = function(what) {
	var index = this._findListener(what);
	if (index == -1) { throw new Error("Cannot find listener to be removed"); }
	this._listeners.splice(index, 1);
	
	if (!this._listeners.length) { this._running = 1; }
	return this;
}

JAK.Timekeeper.prototype._findListener = function(what) {
	for (var i=0;i<this._listeners.length;i++) {
		if (this._listeners[i].what == what) { return i; }
	}
	return -1;
}

JAK.Timekeeper.prototype._tick = function() {
	if (this._running == 1) { this._running = 0; }
	if (this._running == 0) { return; }

	this._schedule(); 	
	for (var i=0;i<this._listeners.length;i++) {
		var item = this._listeners[i];
		item.bucket--;
		if (item.bucket) { continue; } /* jeste ne */
		
		item.bucket = item.count;
		var obj = item.what;
		var method = (typeof(item.method) == "string" ? obj[item.method] : item.method);
		method.call(obj);
	}
}

JAK.Timekeeper.prototype._schedule = function() {
	var s = this._scheduler;
	s(this._tick, null);
}
