<?php declare(strict_types=1);

namespace App\Utils;

use DJTommek\Coordinates\CoordinatesImmutable;

/**
 * Convert coordinates between WGS84 and Uber H3 hexagonal hierarchical geospatial index.
 *
 * Only subset of H3 functionality is implemented: validating cell index, converting cell index to coordinates of
 * cell center and converting coordinates to cell index.
 *
 * @author Uber Technologies, Inc. - Original algorithm and lookup tables (Apache License 2.0)
 * @author Tomas Palider (DJTommek) <tomas@palider.cz> - Ported subset of library from C to PHP
 * @link https://h3geo.org/
 * @link https://github.com/uber/h3/tree/v4.1.0/src/h3lib original source code in C
 */
final class H3
{
	public const int MIN_RESOLUTION = 0;
	public const int MAX_RESOLUTION = 15;

	private const int NUM_BASE_CELLS = 122;
	private const int MAX_FACE_COORD = 2;
	private const int CELL_MODE = 1;

	private const int MODE_OFFSET = 59;
	private const int BASE_CELL_OFFSET = 45;
	private const int RESOLUTION_OFFSET = 52;
	private const int RESERVED_OFFSET = 56;
	private const int PER_DIGIT_OFFSET = 3;
	/** H3 index with mode 0, resolution 0, base cell 0 and all 15 digits set to 7 (invalid digit) */
	private const int INIT = 35184372088831;

	private const int DIGIT_CENTER = 0;
	private const int DIGIT_K_AXES = 1;
	private const int DIGIT_INVALID = 7;

	private const float EPSILON = 0.0000000000000001;
	private const float SQRT3_2 = 0.8660254037844386467637231707529361834714;
	private const float SQRT7 = 2.6457513110645905905016157536392604257102;
	private const float AP7_ROT_RADS = 0.333473172251832115336090755351601070065900389;
	private const float RES0_U_GNOMONIC = 0.38196601125010500003;

	/** Indexes of quadrants in FACE_NEIGHBORS table */
	private const int IJ = 1;
	private const int KI = 2;
	private const int JK = 3;

	/** Unit vectors in IJK coordinates for each H3 digit (direction) */
	private const array UNIT_VECS = [
		[0, 0, 0], // center
		[0, 0, 1], // k-axes
		[0, 1, 0], // j-axes
		[0, 1, 1], // j == k
		[1, 0, 0], // i-axes
		[1, 0, 1], // i == k
		[1, 1, 0], // i == j
	];

	/** Average hexagon area in square meters for each resolution */
	private const array AVERAGE_AREA_M2 = [
		4.357449416078390e+12, 6.097884417941339e+11, 8.680178039899731e+10,
		1.239343465508818e+10, 1.770347654491309e+09, 2.529038581819452e+08,
		3.612906216441250e+07, 5.161293359717198e+06, 7.373275975944188e+05,
		1.053325134272069e+05, 1.504750190766437e+04, 2.149643129451882e+03,
		3.070918756316063e+02, 4.387026794728301e+01, 6.267181135324322e+00,
		8.953115907605802e-01,
	];

	/** Icosahedron face centers in lat/lon radians */
	private const array FACE_CENTER_GEO = [
		[0.803582649718989942, 1.248397419617396099],
		[1.307747883455638156, 2.536945009877921159],
		[1.054751253523952054, -1.347517358900396623],
		[0.600191595538186799, -0.450603909469755746],
		[0.491715428198773866, 0.401988202911306943],
		[0.172745327415618701, 1.678146885280433686],
		[0.605929321571350690, 2.953923329812411617],
		[0.427370518328979641, -1.888876200336285401],
		[-0.079066118549212831, -0.733429513380867741],
		[-0.230961644455383637, 0.506495587332349035],
		[0.079066118549212831, 2.408163140208925497],
		[0.230961644455383637, -2.635097066257444203],
		[-0.172745327415618701, -1.463445768309359553],
		[-0.605929321571350690, -0.187669323777381622],
		[-0.427370518328979641, 1.252716453253507838],
		[-0.600191595538186799, 2.690988744120037492],
		[-0.491715428198773866, -2.739604450678486295],
		[-0.803582649718989942, -1.893195233972397139],
		[-1.307747883455638156, -0.604647643711872080],
		[-1.054751253523952054, 1.794075294689396615],
	];

	/** Icosahedron face centers in x/y/z on the unit sphere */
	private const array FACE_CENTER_POINT = [
		[0.2199307791404606, 0.6583691780274996, 0.7198475378926182],
		[-0.2139234834501421, 0.1478171829550703, 0.9656017935214205],
		[0.1092625278784797, -0.4811951572873210, 0.8697775121287253],
		[0.7428567301586791, -0.3593941678278028, 0.5648005936517033],
		[0.8112534709140969, 0.3448953237639384, 0.4721387736413930],
		[-0.1055498149613921, 0.9794457296411413, 0.1718874610009365],
		[-0.8075407579970092, 0.1533552485898818, 0.5695261994882688],
		[-0.2846148069787907, -0.8644080972654206, 0.4144792552473539],
		[0.7405621473854482, -0.6673299564565524, -0.0789837646326737],
		[0.8512303986474293, 0.4722343788582681, -0.2289137388687808],
		[-0.7405621473854481, 0.6673299564565524, 0.0789837646326737],
		[-0.8512303986474292, -0.4722343788582682, 0.2289137388687808],
		[0.1055498149613919, -0.9794457296411413, -0.1718874610009365],
		[0.8075407579970092, -0.1533552485898819, -0.5695261994882688],
		[0.2846148069787908, 0.8644080972654204, -0.4144792552473539],
		[-0.7428567301586791, 0.3593941678278027, -0.5648005936517033],
		[-0.8112534709140971, -0.3448953237639382, -0.4721387736413930],
		[-0.2199307791404607, -0.6583691780274996, -0.7198475378926182],
		[0.2139234834501420, -0.1478171829550704, -0.9656017935214205],
		[-0.1092625278784796, 0.4811951572873210, -0.8697775121287253],
	];

	/** Icosahedron face IJK axes as azimuth in radians from face center to vertex 0 (only first axis is needed) */
	private const array FACE_AXES_AZ_RADS_CII = [
		5.619958268523939882,
		5.760339081714187279,
		0.780213654393430055,
		0.430469363979999913,
		6.130269123335111400,
		2.692877706530642877,
		2.982963003477243874,
		3.532912002790141181,
		3.494305004259568154,
		3.003214169499538391,
		5.930472956509811562,
		0.138378484090254847,
		0.448714947059150361,
		0.158629650112549365,
		5.891865957979238535,
		2.711123289609793325,
		3.294508837434268316,
		3.804819692245439833,
		3.664438879055192436,
		2.361378999196363184,
	];

	/**
	 * Definition of which faces neighbor each other. For each face there are four items: central face, IJ quadrant,
	 * KI quadrant and JK quadrant. Each item is [face, [translate i, j, k], number of 60° CCW rotations].
	 */
	private const array FACE_NEIGHBORS = [
		[[0, [0, 0, 0], 0], [4, [2, 0, 2], 1], [1, [2, 2, 0], 5], [5, [0, 2, 2], 3]],
		[[1, [0, 0, 0], 0], [0, [2, 0, 2], 1], [2, [2, 2, 0], 5], [6, [0, 2, 2], 3]],
		[[2, [0, 0, 0], 0], [1, [2, 0, 2], 1], [3, [2, 2, 0], 5], [7, [0, 2, 2], 3]],
		[[3, [0, 0, 0], 0], [2, [2, 0, 2], 1], [4, [2, 2, 0], 5], [8, [0, 2, 2], 3]],
		[[4, [0, 0, 0], 0], [3, [2, 0, 2], 1], [0, [2, 2, 0], 5], [9, [0, 2, 2], 3]],
		[[5, [0, 0, 0], 0], [10, [2, 2, 0], 3], [14, [2, 0, 2], 3], [0, [0, 2, 2], 3]],
		[[6, [0, 0, 0], 0], [11, [2, 2, 0], 3], [10, [2, 0, 2], 3], [1, [0, 2, 2], 3]],
		[[7, [0, 0, 0], 0], [12, [2, 2, 0], 3], [11, [2, 0, 2], 3], [2, [0, 2, 2], 3]],
		[[8, [0, 0, 0], 0], [13, [2, 2, 0], 3], [12, [2, 0, 2], 3], [3, [0, 2, 2], 3]],
		[[9, [0, 0, 0], 0], [14, [2, 2, 0], 3], [13, [2, 0, 2], 3], [4, [0, 2, 2], 3]],
		[[10, [0, 0, 0], 0], [5, [2, 2, 0], 3], [6, [2, 0, 2], 3], [15, [0, 2, 2], 3]],
		[[11, [0, 0, 0], 0], [6, [2, 2, 0], 3], [7, [2, 0, 2], 3], [16, [0, 2, 2], 3]],
		[[12, [0, 0, 0], 0], [7, [2, 2, 0], 3], [8, [2, 0, 2], 3], [17, [0, 2, 2], 3]],
		[[13, [0, 0, 0], 0], [8, [2, 2, 0], 3], [9, [2, 0, 2], 3], [18, [0, 2, 2], 3]],
		[[14, [0, 0, 0], 0], [9, [2, 2, 0], 3], [5, [2, 0, 2], 3], [19, [0, 2, 2], 3]],
		[[15, [0, 0, 0], 0], [16, [2, 0, 2], 1], [19, [2, 2, 0], 5], [10, [0, 2, 2], 3]],
		[[16, [0, 0, 0], 0], [17, [2, 0, 2], 1], [15, [2, 2, 0], 5], [11, [0, 2, 2], 3]],
		[[17, [0, 0, 0], 0], [18, [2, 0, 2], 1], [16, [2, 2, 0], 5], [12, [0, 2, 2], 3]],
		[[18, [0, 0, 0], 0], [19, [2, 0, 2], 1], [17, [2, 2, 0], 5], [13, [0, 2, 2], 3]],
		[[19, [0, 0, 0], 0], [15, [2, 0, 2], 1], [18, [2, 2, 0], 5], [14, [0, 2, 2], 3]],
	];

	/** Maximum IJK dimension for Class II resolutions (odd resolutions are not used) */
	private const array MAX_DIM_BY_CII_RES = [2, -1, 14, -1, 98, -1, 686, -1, 4802, -1, 33614, -1, 235298, -1, 1647086, -1, 11529602];
	/** Unit scale distance for Class II resolutions (odd resolutions are not used) */
	private const array UNIT_SCALE_BY_CII_RES = [1, -1, 7, -1, 49, -1, 343, -1, 2401, -1, 16807, -1, 117649, -1, 823543, -1, 5764801];

	/**
	 * Resolution 0 base cell lookup table for each face. For each face there are 27 items indexed by i * 9 + j * 3 + k
	 * of IJK coordinates (0-2), each item is [base cell, number of 60° CCW rotations into base cell coordinate system].
	 *
	 * Generated from faceIjkBaseCells table in baseCells.c
	 */
	private const array FACE_IJK_BASE_CELLS = [
		[[16, 0], [18, 0], [24, 0], [33, 0], [30, 0], [32, 3], [49, 1], [48, 3], [50, 3], [8, 0], [5, 5], [10, 5], [22, 0], [16, 0], [18, 0], [41, 1], [33, 0], [30, 0], [4, 0], [0, 5], [2, 5], [15, 1], [8, 0], [5, 5], [31, 1], [22, 0], [16, 0]], // face 0
		[[2, 0], [6, 0], [14, 0], [10, 0], [11, 0], [17, 3], [24, 1], [23, 3], [25, 3], [0, 0], [1, 5], [9, 5], [5, 0], [2, 0], [6, 0], [18, 1], [10, 0], [11, 0], [4, 1], [3, 5], [7, 5], [8, 1], [0, 0], [1, 5], [16, 1], [5, 0], [2, 0]], // face 1
		[[7, 0], [21, 0], [38, 0], [9, 0], [19, 0], [34, 3], [14, 1], [20, 3], [36, 3], [3, 0], [13, 5], [29, 5], [1, 0], [7, 0], [21, 0], [6, 1], [9, 0], [19, 0], [4, 2], [12, 5], [26, 5], [0, 1], [3, 0], [13, 5], [2, 1], [1, 0], [7, 0]], // face 2
		[[26, 0], [42, 0], [58, 0], [29, 0], [43, 0], [62, 3], [38, 1], [47, 3], [64, 3], [12, 0], [28, 5], [44, 5], [13, 0], [26, 0], [42, 0], [21, 1], [29, 0], [43, 0], [4, 3], [15, 5], [31, 5], [3, 1], [12, 0], [28, 5], [7, 1], [13, 0], [26, 0]], // face 3
		[[31, 0], [41, 0], [49, 0], [44, 0], [53, 0], [61, 3], [58, 1], [65, 3], [75, 3], [15, 0], [22, 5], [33, 5], [28, 0], [31, 0], [41, 0], [42, 1], [44, 0], [53, 0], [4, 4], [8, 5], [16, 5], [12, 1], [15, 0], [22, 5], [26, 1], [28, 0], [31, 0]], // face 4
		[[50, 0], [48, 0], [49, 3], [32, 0], [30, 3], [33, 3], [24, 3], [18, 3], [16, 3], [70, 0], [67, 0], [66, 3], [52, 3], [50, 0], [48, 0], [37, 3], [32, 0], [30, 3], [83, 0], [87, 3], [85, 3], [74, 3], [70, 0], [67, 0], [57, 1], [52, 3], [50, 0]], // face 5
		[[25, 0], [23, 0], [24, 3], [17, 0], [11, 3], [10, 3], [14, 3], [6, 3], [2, 3], [45, 0], [39, 0], [37, 3], [35, 3], [25, 0], [23, 0], [27, 3], [17, 0], [11, 3], [63, 0], [59, 3], [57, 3], [56, 3], [45, 0], [39, 0], [46, 3], [35, 3], [25, 0]], // face 6
		[[36, 0], [20, 0], [14, 3], [34, 0], [19, 3], [9, 3], [38, 3], [21, 3], [7, 3], [55, 0], [40, 0], [27, 3], [54, 3], [36, 0], [20, 0], [51, 3], [34, 0], [19, 3], [72, 0], [60, 3], [46, 3], [73, 3], [55, 0], [40, 0], [71, 3], [54, 3], [36, 0]], // face 7
		[[64, 0], [47, 0], [38, 3], [62, 0], [43, 3], [29, 3], [58, 3], [42, 3], [26, 3], [84, 0], [69, 0], [51, 3], [82, 3], [64, 0], [47, 0], [76, 3], [62, 0], [43, 3], [97, 0], [89, 3], [71, 3], [98, 3], [84, 0], [69, 0], [96, 3], [82, 3], [64, 0]], // face 8
		[[75, 0], [65, 0], [58, 3], [61, 0], [53, 3], [44, 3], [49, 3], [41, 3], [31, 3], [94, 0], [86, 0], [76, 3], [81, 3], [75, 0], [65, 0], [66, 3], [61, 0], [53, 3], [107, 0], [104, 3], [96, 3], [101, 3], [94, 0], [86, 0], [85, 3], [81, 3], [75, 0]], // face 9
		[[57, 0], [59, 0], [63, 3], [74, 0], [78, 3], [79, 3], [83, 3], [92, 3], [95, 3], [37, 0], [39, 3], [45, 3], [52, 0], [57, 0], [59, 0], [70, 3], [74, 0], [78, 3], [24, 0], [23, 3], [25, 3], [32, 3], [37, 0], [39, 3], [50, 3], [52, 0], [57, 0]], // face 10
		[[46, 0], [60, 0], [72, 3], [56, 0], [68, 3], [80, 3], [63, 3], [77, 3], [90, 3], [27, 0], [40, 3], [55, 3], [35, 0], [46, 0], [60, 0], [45, 3], [56, 0], [68, 3], [14, 0], [20, 3], [36, 3], [17, 3], [27, 0], [40, 3], [25, 3], [35, 0], [46, 0]], // face 11
		[[71, 0], [89, 0], [97, 3], [73, 0], [91, 3], [103, 3], [72, 3], [88, 3], [105, 3], [51, 0], [69, 3], [84, 3], [54, 0], [71, 0], [89, 0], [55, 3], [73, 0], [91, 3], [38, 0], [47, 3], [64, 3], [34, 3], [51, 0], [69, 3], [36, 3], [54, 0], [71, 0]], // face 12
		[[96, 0], [104, 0], [107, 3], [98, 0], [110, 3], [115, 3], [97, 3], [111, 3], [119, 3], [76, 0], [86, 3], [94, 3], [82, 0], [96, 0], [104, 0], [84, 3], [98, 0], [110, 3], [58, 0], [65, 3], [75, 3], [62, 3], [76, 0], [86, 3], [64, 3], [82, 0], [96, 0]], // face 13
		[[85, 0], [87, 0], [83, 3], [101, 0], [102, 3], [100, 3], [107, 3], [112, 3], [114, 3], [66, 0], [67, 3], [70, 3], [81, 0], [85, 0], [87, 0], [94, 3], [101, 0], [102, 3], [49, 0], [48, 3], [50, 3], [61, 3], [66, 0], [67, 3], [75, 3], [81, 0], [85, 0]], // face 14
		[[95, 0], [92, 0], [83, 0], [79, 0], [78, 0], [74, 3], [63, 1], [59, 3], [57, 3], [109, 0], [108, 0], [100, 5], [93, 1], [95, 0], [92, 0], [77, 1], [79, 0], [78, 0], [117, 4], [118, 5], [114, 5], [106, 1], [109, 0], [108, 0], [90, 1], [93, 1], [95, 0]], // face 15
		[[90, 0], [77, 0], [63, 0], [80, 0], [68, 0], [56, 3], [72, 1], [60, 3], [46, 3], [106, 0], [93, 0], [79, 5], [99, 1], [90, 0], [77, 0], [88, 1], [80, 0], [68, 0], [117, 3], [109, 5], [95, 5], [113, 1], [106, 0], [93, 0], [105, 1], [99, 1], [90, 0]], // face 16
		[[105, 0], [88, 0], [72, 0], [103, 0], [91, 0], [73, 3], [97, 1], [89, 3], [71, 3], [113, 0], [99, 0], [80, 5], [116, 1], [105, 0], [88, 0], [111, 1], [103, 0], [91, 0], [117, 2], [106, 5], [90, 5], [121, 1], [113, 0], [99, 0], [119, 1], [116, 1], [105, 0]], // face 17
		[[119, 0], [111, 0], [97, 0], [115, 0], [110, 0], [98, 3], [107, 1], [104, 3], [96, 3], [121, 0], [116, 0], [103, 5], [120, 1], [119, 0], [111, 0], [112, 1], [115, 0], [110, 0], [117, 1], [113, 5], [105, 5], [118, 1], [121, 0], [116, 0], [114, 1], [120, 1], [119, 0]], // face 18
		[[114, 0], [112, 0], [107, 0], [100, 0], [102, 0], [101, 3], [83, 1], [87, 3], [85, 3], [118, 0], [120, 0], [115, 5], [108, 1], [114, 0], [112, 0], [92, 1], [100, 0], [102, 0], [117, 0], [121, 5], [119, 5], [109, 1], [118, 0], [120, 0], [95, 1], [108, 1], [114, 0]], // face 19
	];

	/**
	 * Resolution 0 base cell data. For each base cell: [home face, i, j, k, is pentagon, clockwise offset face 1,
	 * clockwise offset face 2].
	 *
	 * Generated from baseCellData table in baseCells.c
	 */
	private const array BASE_CELL_DATA = [
		[1, 1, 0, 0, 0, 0, 0], // 0
		[2, 1, 1, 0, 0, 0, 0], // 1
		[1, 0, 0, 0, 0, 0, 0], // 2
		[2, 1, 0, 0, 0, 0, 0], // 3
		[0, 2, 0, 0, 1, -1, -1], // 4
		[1, 1, 1, 0, 0, 0, 0], // 5
		[1, 0, 0, 1, 0, 0, 0], // 6
		[2, 0, 0, 0, 0, 0, 0], // 7
		[0, 1, 0, 0, 0, 0, 0], // 8
		[2, 0, 1, 0, 0, 0, 0], // 9
		[1, 0, 1, 0, 0, 0, 0], // 10
		[1, 0, 1, 1, 0, 0, 0], // 11
		[3, 1, 0, 0, 0, 0, 0], // 12
		[3, 1, 1, 0, 0, 0, 0], // 13
		[11, 2, 0, 0, 1, 2, 6], // 14
		[4, 1, 0, 0, 0, 0, 0], // 15
		[0, 0, 0, 0, 0, 0, 0], // 16
		[6, 0, 1, 0, 0, 0, 0], // 17
		[0, 0, 0, 1, 0, 0, 0], // 18
		[2, 0, 1, 1, 0, 0, 0], // 19
		[7, 0, 0, 1, 0, 0, 0], // 20
		[2, 0, 0, 1, 0, 0, 0], // 21
		[0, 1, 1, 0, 0, 0, 0], // 22
		[6, 0, 0, 1, 0, 0, 0], // 23
		[10, 2, 0, 0, 1, 1, 5], // 24
		[6, 0, 0, 0, 0, 0, 0], // 25
		[3, 0, 0, 0, 0, 0, 0], // 26
		[11, 1, 0, 0, 0, 0, 0], // 27
		[4, 1, 1, 0, 0, 0, 0], // 28
		[3, 0, 1, 0, 0, 0, 0], // 29
		[0, 0, 1, 1, 0, 0, 0], // 30
		[4, 0, 0, 0, 0, 0, 0], // 31
		[5, 0, 1, 0, 0, 0, 0], // 32
		[0, 0, 1, 0, 0, 0, 0], // 33
		[7, 0, 1, 0, 0, 0, 0], // 34
		[11, 1, 1, 0, 0, 0, 0], // 35
		[7, 0, 0, 0, 0, 0, 0], // 36
		[10, 1, 0, 0, 0, 0, 0], // 37
		[12, 2, 0, 0, 1, 3, 7], // 38
		[6, 1, 0, 1, 0, 0, 0], // 39
		[7, 1, 0, 1, 0, 0, 0], // 40
		[4, 0, 0, 1, 0, 0, 0], // 41
		[3, 0, 0, 1, 0, 0, 0], // 42
		[3, 0, 1, 1, 0, 0, 0], // 43
		[4, 0, 1, 0, 0, 0, 0], // 44
		[6, 1, 0, 0, 0, 0, 0], // 45
		[11, 0, 0, 0, 0, 0, 0], // 46
		[8, 0, 0, 1, 0, 0, 0], // 47
		[5, 0, 0, 1, 0, 0, 0], // 48
		[14, 2, 0, 0, 1, 0, 9], // 49
		[5, 0, 0, 0, 0, 0, 0], // 50
		[12, 1, 0, 0, 0, 0, 0], // 51
		[10, 1, 1, 0, 0, 0, 0], // 52
		[4, 0, 1, 1, 0, 0, 0], // 53
		[12, 1, 1, 0, 0, 0, 0], // 54
		[7, 1, 0, 0, 0, 0, 0], // 55
		[11, 0, 1, 0, 0, 0, 0], // 56
		[10, 0, 0, 0, 0, 0, 0], // 57
		[13, 2, 0, 0, 1, 4, 8], // 58
		[10, 0, 0, 1, 0, 0, 0], // 59
		[11, 0, 0, 1, 0, 0, 0], // 60
		[9, 0, 1, 0, 0, 0, 0], // 61
		[8, 0, 1, 0, 0, 0, 0], // 62
		[6, 2, 0, 0, 1, 11, 15], // 63
		[8, 0, 0, 0, 0, 0, 0], // 64
		[9, 0, 0, 1, 0, 0, 0], // 65
		[14, 1, 0, 0, 0, 0, 0], // 66
		[5, 1, 0, 1, 0, 0, 0], // 67
		[16, 0, 1, 1, 0, 0, 0], // 68
		[8, 1, 0, 1, 0, 0, 0], // 69
		[5, 1, 0, 0, 0, 0, 0], // 70
		[12, 0, 0, 0, 0, 0, 0], // 71
		[7, 2, 0, 0, 1, 12, 16], // 72
		[12, 0, 1, 0, 0, 0, 0], // 73
		[10, 0, 1, 0, 0, 0, 0], // 74
		[9, 0, 0, 0, 0, 0, 0], // 75
		[13, 1, 0, 0, 0, 0, 0], // 76
		[16, 0, 0, 1, 0, 0, 0], // 77
		[15, 0, 1, 1, 0, 0, 0], // 78
		[15, 0, 1, 0, 0, 0, 0], // 79
		[16, 0, 1, 0, 0, 0, 0], // 80
		[14, 1, 1, 0, 0, 0, 0], // 81
		[13, 1, 1, 0, 0, 0, 0], // 82
		[5, 2, 0, 0, 1, 10, 19], // 83
		[8, 1, 0, 0, 0, 0, 0], // 84
		[14, 0, 0, 0, 0, 0, 0], // 85
		[9, 1, 0, 1, 0, 0, 0], // 86
		[14, 0, 0, 1, 0, 0, 0], // 87
		[17, 0, 0, 1, 0, 0, 0], // 88
		[12, 0, 0, 1, 0, 0, 0], // 89
		[16, 0, 0, 0, 0, 0, 0], // 90
		[17, 0, 1, 1, 0, 0, 0], // 91
		[15, 0, 0, 1, 0, 0, 0], // 92
		[16, 1, 0, 1, 0, 0, 0], // 93
		[9, 1, 0, 0, 0, 0, 0], // 94
		[15, 0, 0, 0, 0, 0, 0], // 95
		[13, 0, 0, 0, 0, 0, 0], // 96
		[8, 2, 0, 0, 1, 13, 17], // 97
		[13, 0, 1, 0, 0, 0, 0], // 98
		[17, 1, 0, 1, 0, 0, 0], // 99
		[19, 0, 1, 0, 0, 0, 0], // 100
		[14, 0, 1, 0, 0, 0, 0], // 101
		[19, 0, 1, 1, 0, 0, 0], // 102
		[17, 0, 1, 0, 0, 0, 0], // 103
		[13, 0, 0, 1, 0, 0, 0], // 104
		[17, 0, 0, 0, 0, 0, 0], // 105
		[16, 1, 0, 0, 0, 0, 0], // 106
		[9, 2, 0, 0, 1, 14, 18], // 107
		[15, 1, 0, 1, 0, 0, 0], // 108
		[15, 1, 0, 0, 0, 0, 0], // 109
		[18, 0, 1, 1, 0, 0, 0], // 110
		[18, 0, 0, 1, 0, 0, 0], // 111
		[19, 0, 0, 1, 0, 0, 0], // 112
		[17, 1, 0, 0, 0, 0, 0], // 113
		[19, 0, 0, 0, 0, 0, 0], // 114
		[18, 0, 1, 0, 0, 0, 0], // 115
		[18, 1, 0, 1, 0, 0, 0], // 116
		[19, 2, 0, 0, 1, -1, -1], // 117
		[19, 1, 0, 0, 0, 0, 0], // 118
		[18, 0, 0, 0, 0, 0, 0], // 119
		[19, 1, 0, 1, 0, 0, 0], // 120
		[18, 1, 0, 0, 0, 0, 0], // 121
	];

	/**
	 * Check if string is valid H3 cell index, eg. '8d1e3541e32aa7f'. Case-insensitive.
	 */
	public static function isValid(string $index): bool
	{
		return self::parse($index) !== null;
	}

	/**
	 * Get resolution (0-15) of H3 cell index.
	 *
	 * @throws \InvalidArgumentException if index is not valid H3 cell index
	 */
	public static function getResolution(string $index): int
	{
		return self::getIndexResolution(self::parseOrThrow($index));
	}

	/**
	 * Average area of hexagon cell in square meters on given resolution.
	 */
	public static function getAverageAreaM2(int $resolution): float
	{
		self::assertResolution($resolution);
		return self::AVERAGE_AREA_M2[$resolution];
	}

	/**
	 * Convert H3 cell index into coordinates of center of that cell.
	 *
	 * @throws \InvalidArgumentException if index is not valid H3 cell index
	 */
	public static function toCoordinates(string $index): CoordinatesImmutable
	{
		$h = self::parseOrThrow($index);
		[$face, $ijk] = self::indexToFaceIjk($h);
		[$lat, $lon] = self::faceIjkToGeo($face, $ijk, self::getIndexResolution($h));

		return new CoordinatesImmutable(rad2deg($lat), rad2deg($lon));
	}

	/**
	 * Convert coordinates into H3 cell index on given resolution.
	 *
	 * @param int $resolution 0 (largest cells, ~4.3 million km²) to 15 (smallest cells, ~0.9 m²)
	 * @return string H3 cell index in lowercase hexadecimal format, eg. '8d1e3541e32aa7f'
	 */
	public static function fromCoordinates(float $lat, float $lon, int $resolution): string
	{
		self::assertResolution($resolution);
		if (is_finite($lat) === false || is_finite($lon) === false) {
			throw new \InvalidArgumentException('Coordinates must be finite numbers.');
		}

		[$face, $ijk] = self::geoToFaceIjk(deg2rad($lat), deg2rad($lon), $resolution);
		$h = self::faceIjkToIndex($face, $ijk, $resolution);
		if ($h === null) {
			throw new \RuntimeException(sprintf('Unable to convert coordinates %F,%F to H3 index.', $lat, $lon));
		}
		return sprintf('%x', $h);
	}

	private static function assertResolution(int $resolution): void
	{
		if ($resolution < self::MIN_RESOLUTION || $resolution > self::MAX_RESOLUTION) {
			throw new \InvalidArgumentException(sprintf(
				'Resolution must be between %d and %d, %d provided.',
				self::MIN_RESOLUTION,
				self::MAX_RESOLUTION,
				$resolution,
			));
		}
	}

	private static function parseOrThrow(string $index): int
	{
		$h = self::parse($index);
		if ($h === null) {
			throw new \InvalidArgumentException(sprintf('Invalid H3 cell index "%s".', $index));
		}
		return $h;
	}

	/**
	 * Parse and validate H3 cell index from hexadecimal string.
	 *
	 * @return ?int Parsed index or null if it is not valid H3 cell index
	 */
	private static function parse(string $index): ?int
	{
		// Highest bit of valid index is always 0, so 16th character (if present) must be lower than 8, which means
		// that every valid index fits into PHP signed 64-bit integer.
		if (preg_match('/^[0-7]?[0-9a-f]{1,15}$/i', $index) !== 1) {
			return null;
		}

		$h = intval($index, 16);
		return self::isValidIndex($h) ? $h : null;
	}

	/**
	 * Port of isValidCell() from h3Index.c
	 */
	private static function isValidIndex(int $h): bool
	{
		if ($h < 0) { // high bit
			return false;
		}
		if ((($h >> self::MODE_OFFSET) & 15) !== self::CELL_MODE) {
			return false;
		}
		if ((($h >> self::RESERVED_OFFSET) & 7) !== 0) {
			return false;
		}

		$baseCell = self::getIndexBaseCell($h);
		if ($baseCell >= self::NUM_BASE_CELLS) {
			return false;
		}

		$res = self::getIndexResolution($h);
		$foundFirstNonZeroDigit = false;
		for ($r = 1; $r <= $res; $r++) {
			$digit = self::getIndexDigit($h, $r);
			if ($foundFirstNonZeroDigit === false && $digit !== self::DIGIT_CENTER) {
				$foundFirstNonZeroDigit = true;
				if (self::isBaseCellPentagon($baseCell) && $digit === self::DIGIT_K_AXES) {
					return false;
				}
			}
			if ($digit === self::DIGIT_INVALID) {
				return false;
			}
		}

		for ($r = $res + 1; $r <= self::MAX_RESOLUTION; $r++) {
			if (self::getIndexDigit($h, $r) !== self::DIGIT_INVALID) {
				return false;
			}
		}

		return true;
	}

	private static function getIndexResolution(int $h): int
	{
		return ($h >> self::RESOLUTION_OFFSET) & 15;
	}

	private static function getIndexBaseCell(int $h): int
	{
		return ($h >> self::BASE_CELL_OFFSET) & 127;
	}

	private static function getIndexDigit(int $h, int $res): int
	{
		return ($h >> ((self::MAX_RESOLUTION - $res) * self::PER_DIGIT_OFFSET)) & 7;
	}

	private static function setIndexDigit(int $h, int $res, int $digit): int
	{
		$offset = (self::MAX_RESOLUTION - $res) * self::PER_DIGIT_OFFSET;
		return ($h & ~(7 << $offset)) | ($digit << $offset);
	}

	private static function isBaseCellPentagon(int $baseCell): bool
	{
		return self::BASE_CELL_DATA[$baseCell][4] === 1;
	}

	private static function isResolutionClassIII(int $res): bool
	{
		return $res % 2 === 1;
	}

	/**
	 * Highest resolution non-zero digit in index or 0 if all digits are zero.
	 */
	private static function leadingNonZeroDigit(int $h): int
	{
		$res = self::getIndexResolution($h);
		for ($r = 1; $r <= $res; $r++) {
			$digit = self::getIndexDigit($h, $r);
			if ($digit !== self::DIGIT_CENTER) {
				return $digit;
			}
		}
		return self::DIGIT_CENTER;
	}

	/*
	 * ---------------------------------------------------------------------------------------------------------------
	 * Index rotations (h3Index.c)
	 * ---------------------------------------------------------------------------------------------------------------
	 */

	private static function rotateDigit60ccw(int $digit): int
	{
		return match ($digit) {
			1 => 5, // K -> IK
			5 => 4, // IK -> I
			4 => 6, // I -> IJ
			6 => 2, // IJ -> J
			2 => 3, // J -> JK
			3 => 1, // JK -> K
			default => $digit,
		};
	}

	private static function rotateDigit60cw(int $digit): int
	{
		return match ($digit) {
			1 => 3, // K -> JK
			3 => 2, // JK -> J
			2 => 6, // J -> IJ
			6 => 4, // IJ -> I
			4 => 5, // I -> IK
			5 => 1, // IK -> K
			default => $digit,
		};
	}

	private static function indexRotate60ccw(int $h): int
	{
		$res = self::getIndexResolution($h);
		for ($r = 1; $r <= $res; $r++) {
			$h = self::setIndexDigit($h, $r, self::rotateDigit60ccw(self::getIndexDigit($h, $r)));
		}
		return $h;
	}

	private static function indexRotate60cw(int $h): int
	{
		$res = self::getIndexResolution($h);
		for ($r = 1; $r <= $res; $r++) {
			$h = self::setIndexDigit($h, $r, self::rotateDigit60cw(self::getIndexDigit($h, $r)));
		}
		return $h;
	}

	/**
	 * Rotate index 60° counter-clockwise about a pentagonal center.
	 */
	private static function indexRotatePent60ccw(int $h): int
	{
		$foundFirstNonZeroDigit = false;
		$res = self::getIndexResolution($h);
		for ($r = 1; $r <= $res; $r++) {
			$h = self::setIndexDigit($h, $r, self::rotateDigit60ccw(self::getIndexDigit($h, $r)));

			// look for the first non-zero digit, so we can adjust for deleted k-axes sequence if necessary
			if ($foundFirstNonZeroDigit === false && self::getIndexDigit($h, $r) !== self::DIGIT_CENTER) {
				$foundFirstNonZeroDigit = true;
				if (self::leadingNonZeroDigit($h) === self::DIGIT_K_AXES) {
					$h = self::indexRotate60ccw($h);
				}
			}
		}
		return $h;
	}

	/*
	 * ---------------------------------------------------------------------------------------------------------------
	 * IJK coordinates (coordijk.c)
	 * Coordinates are represented as array [i, j, k]
	 * ---------------------------------------------------------------------------------------------------------------
	 */

	/**
	 * @param array{int, int, int} $c
	 * @return array{int, int, int}
	 */
	private static function ijkNormalize(array $c): array
	{
		[$i, $j, $k] = $c;
		// remove any negative values
		if ($i < 0) {
			$j -= $i;
			$k -= $i;
			$i = 0;
		}
		if ($j < 0) {
			$i -= $j;
			$k -= $j;
			$j = 0;
		}
		if ($k < 0) {
			$i -= $k;
			$j -= $k;
			$k = 0;
		}

		// remove the min value if needed
		$min = min($i, $j, $k);
		if ($min > 0) {
			$i -= $min;
			$j -= $min;
			$k -= $min;
		}
		return [$i, $j, $k];
	}

	/**
	 * @param array{int, int, int} $a
	 * @param array{int, int, int} $b
	 * @return array{int, int, int}
	 */
	private static function ijkAdd(array $a, array $b): array
	{
		return [$a[0] + $b[0], $a[1] + $b[1], $a[2] + $b[2]];
	}

	/**
	 * @param array{int, int, int} $a
	 * @param array{int, int, int} $b
	 * @return array{int, int, int}
	 */
	private static function ijkSub(array $a, array $b): array
	{
		return [$a[0] - $b[0], $a[1] - $b[1], $a[2] - $b[2]];
	}

	/**
	 * @param array{int, int, int} $c
	 * @return array{int, int, int}
	 */
	private static function ijkScale(array $c, int $factor): array
	{
		return [$c[0] * $factor, $c[1] * $factor, $c[2] * $factor];
	}

	/**
	 * Combine IJK coordinates with transformation unit vectors, eg. for rotation or aperture change.
	 *
	 * @param array{int, int, int} $c
	 * @param array{int, int, int} $iVec
	 * @param array{int, int, int} $jVec
	 * @param array{int, int, int} $kVec
	 * @return array{int, int, int}
	 */
	private static function ijkTransform(array $c, array $iVec, array $jVec, array $kVec): array
	{
		return self::ijkNormalize(self::ijkAdd(
			self::ijkAdd(self::ijkScale($iVec, $c[0]), self::ijkScale($jVec, $c[1])),
			self::ijkScale($kVec, $c[2]),
		));
	}

	/**
	 * @param array{int, int, int} $c
	 * @return array{int, int, int}
	 */
	private static function ijkRotate60ccw(array $c): array
	{
		return self::ijkTransform($c, [1, 1, 0], [0, 1, 1], [1, 0, 1]);
	}

	/**
	 * @param array{int, int, int} $c
	 * @return array{int, int, int}
	 */
	private static function ijkRotate60cw(array $c): array
	{
		return self::ijkTransform($c, [1, 0, 1], [1, 1, 0], [0, 1, 1]);
	}

	/**
	 * Find the normalized IJK coordinates of the indexing parent of a cell in a counter-clockwise aperture 7 grid.
	 *
	 * @param array{int, int, int} $c
	 * @return array{int, int, int}
	 */
	private static function upAp7(array $c): array
	{
		$i = $c[0] - $c[2];
		$j = $c[1] - $c[2];
		return self::ijkNormalize([
			(int)round((3 * $i - $j) / 7),
			(int)round(($i + 2 * $j) / 7),
			0,
		]);
	}

	/**
	 * Find the normalized IJK coordinates of the indexing parent of a cell in a clockwise aperture 7 grid.
	 *
	 * @param array{int, int, int} $c
	 * @return array{int, int, int}
	 */
	private static function upAp7r(array $c): array
	{
		$i = $c[0] - $c[2];
		$j = $c[1] - $c[2];
		return self::ijkNormalize([
			(int)round((2 * $i + $j) / 7),
			(int)round((3 * $j - $i) / 7),
			0,
		]);
	}

	/**
	 * Find the normalized IJK coordinates of the hex centered on the indicated hex at the next finer aperture 7
	 * counter-clockwise resolution.
	 *
	 * @param array{int, int, int} $c
	 * @return array{int, int, int}
	 */
	private static function downAp7(array $c): array
	{
		return self::ijkTransform($c, [3, 0, 1], [1, 3, 0], [0, 1, 3]);
	}

	/**
	 * Find the normalized IJK coordinates of the hex centered on the indicated hex at the next finer aperture 7
	 * clockwise resolution.
	 *
	 * @param array{int, int, int} $c
	 * @return array{int, int, int}
	 */
	private static function downAp7r(array $c): array
	{
		return self::ijkTransform($c, [3, 1, 0], [0, 3, 1], [1, 0, 3]);
	}

	/**
	 * @param array{int, int, int} $c
	 * @return array{int, int, int}
	 */
	private static function neighbor(array $c, int $digit): array
	{
		if ($digit > self::DIGIT_CENTER && $digit < self::DIGIT_INVALID) {
			$c = self::ijkNormalize(self::ijkAdd($c, self::UNIT_VECS[$digit]));
		}
		return $c;
	}

	/**
	 * @param array{int, int, int} $c
	 */
	private static function unitIjkToDigit(array $c): int
	{
		$c = self::ijkNormalize($c);
		foreach (self::UNIT_VECS as $digit => $unitVec) {
			if ($c === $unitVec) {
				return $digit;
			}
		}
		return self::DIGIT_INVALID;
	}

	/**
	 * Find the center point in 2D cartesian coordinates of a hex.
	 *
	 * @param array{int, int, int} $c
	 * @return array{float, float}
	 */
	private static function ijkToHex2d(array $c): array
	{
		$i = $c[0] - $c[2];
		$j = $c[1] - $c[2];
		return [$i - 0.5 * $j, $j * self::SQRT3_2];
	}

	/**
	 * Determine the containing hex in IJK coordinates of a 2D cartesian coordinate vector.
	 *
	 * @return array{int, int, int}
	 */
	private static function hex2dToIjk(float $x, float $y): array
	{
		$a1 = abs($x);
		$a2 = abs($y);

		// first do a reverse conversion
		$x2 = $a2 / self::SQRT3_2;
		$x1 = $a1 + $x2 / 2.0;

		// check if we have the center of a hex
		$m1 = (int)$x1;
		$m2 = (int)$x2;

		// otherwise round correctly
		$r1 = $x1 - $m1;
		$r2 = $x2 - $m2;

		if ($r1 < 0.5) {
			if ($r1 < 1.0 / 3.0) {
				$i = $m1;
				$j = ($r2 < (1.0 + $r1) / 2.0) ? $m2 : $m2 + 1;
			} else {
				$j = ($r2 < (1.0 - $r1)) ? $m2 : $m2 + 1;
				$i = ((1.0 - $r1) <= $r2 && $r2 < (2.0 * $r1)) ? $m1 + 1 : $m1;
			}
		} else {
			if ($r1 < 2.0 / 3.0) {
				$j = ($r2 < (1.0 - $r1)) ? $m2 : $m2 + 1;
				$i = ((2.0 * $r1 - 1.0) < $r2 && $r2 < (1.0 - $r1)) ? $m1 : $m1 + 1;
			} else {
				$i = $m1 + 1;
				$j = ($r2 < ($r1 / 2.0)) ? $m2 : $m2 + 1;
			}
		}

		// now fold across the axes if necessary
		if ($x < 0.0) {
			if ($j % 2 === 0) {
				$axisI = intdiv($j, 2);
				$i -= 2 * ($i - $axisI);
			} else {
				$axisI = intdiv($j + 1, 2);
				$i -= 2 * ($i - $axisI) + 1;
			}
		}

		if ($y < 0.0) {
			$i -= intdiv(2 * $j + 1, 2);
			$j = -$j;
		}

		return self::ijkNormalize([$i, $j, 0]);
	}

	/*
	 * ---------------------------------------------------------------------------------------------------------------
	 * Spherical coordinates (latLng.c)
	 * ---------------------------------------------------------------------------------------------------------------
	 */

	private static function posAngleRads(float $rads): float
	{
		$tmp = $rads < 0.0 ? $rads + 2 * M_PI : $rads;
		if ($rads >= 2 * M_PI) {
			$tmp -= 2 * M_PI;
		}
		return $tmp;
	}

	private static function constrainLon(float $lon): float
	{
		while ($lon > M_PI) {
			$lon -= 2 * M_PI;
		}
		while ($lon < -M_PI) {
			$lon += 2 * M_PI;
		}
		return $lon;
	}

	/**
	 * Azimuth in radians from point 1 to point 2.
	 */
	private static function geoAzimuthRads(float $lat1, float $lon1, float $lat2, float $lon2): float
	{
		return atan2(
			cos($lat2) * sin($lon2 - $lon1),
			cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lon2 - $lon1),
		);
	}

	/**
	 * Compute the point on the sphere in specified azimuth and distance from another point.
	 *
	 * @return array{float, float} [lat, lon] in radians
	 */
	private static function geoAzDistanceRads(float $lat1, float $lon1, float $az, float $distance): array
	{
		if ($distance < self::EPSILON) {
			return [$lat1, $lon1];
		}

		$az = self::posAngleRads($az);

		// check for due north/south azimuth
		if ($az < self::EPSILON || abs($az - M_PI) < self::EPSILON) {
			$lat2 = $az < self::EPSILON ? $lat1 + $distance : $lat1 - $distance;

			if (abs($lat2 - M_PI_2) < self::EPSILON) { // north pole
				return [M_PI_2, 0.0];
			}
			if (abs($lat2 + M_PI_2) < self::EPSILON) { // south pole
				return [-M_PI_2, 0.0];
			}
			return [$lat2, self::constrainLon($lon1)];
		}

		$sinLat = sin($lat1) * cos($distance) + cos($lat1) * sin($distance) * cos($az);
		$sinLat = max(-1.0, min(1.0, $sinLat));
		$lat2 = asin($sinLat);
		if (abs($lat2 - M_PI_2) < self::EPSILON) { // north pole
			return [M_PI_2, 0.0];
		}
		if (abs($lat2 + M_PI_2) < self::EPSILON) { // south pole
			return [-M_PI_2, 0.0];
		}

		$sinLon = sin($az) * sin($distance) / cos($lat2);
		$cosLon = (cos($distance) - sin($lat1) * sin($lat2)) / cos($lat1) / cos($lat2);
		$sinLon = max(-1.0, min(1.0, $sinLon));
		$cosLon = max(-1.0, min(1.0, $cosLon));
		return [$lat2, self::constrainLon($lon1 + atan2($sinLon, $cosLon))];
	}

	/*
	 * ---------------------------------------------------------------------------------------------------------------
	 * Icosahedron faces (faceijk.c)
	 * ---------------------------------------------------------------------------------------------------------------
	 */

	/**
	 * Encode coordinates on the sphere to the FaceIJK address of the containing cell at the specified resolution.
	 *
	 * @return array{int, array{int, int, int}} [face, ijk]
	 */
	private static function geoToFaceIjk(float $lat, float $lon, int $res): array
	{
		// determine the icosahedron face
		$cosLat = cos($lat);
		$point = [$cosLat * cos($lon), $cosLat * sin($lon), sin($lat)];
		$face = 0;
		$sqd = 5.0; // square of distance between two points on unit sphere is always less or equal than 4.0
		foreach (self::FACE_CENTER_POINT as $f => $center) {
			$sqdT = ($center[0] - $point[0]) ** 2 + ($center[1] - $point[1]) ** 2 + ($center[2] - $point[2]) ** 2;
			if ($sqdT < $sqd) {
				$face = $f;
				$sqd = $sqdT;
			}
		}

		// cos(r) = 1 - 2 * sin^2(r/2) = 1 - 2 * (sqd / 4) = 1 - sqd/2
		$r = acos(1 - $sqd / 2);
		if ($r < self::EPSILON) {
			return [$face, self::hex2dToIjk(0.0, 0.0)];
		}

		// now have face and r, now find CCW theta from CII i-axis
		[$faceLat, $faceLon] = self::FACE_CENTER_GEO[$face];
		$theta = self::posAngleRads(
			self::FACE_AXES_AZ_RADS_CII[$face] - self::posAngleRads(self::geoAzimuthRads($faceLat, $faceLon, $lat, $lon)),
		);

		// adjust theta for Class III (odd resolutions)
		if (self::isResolutionClassIII($res)) {
			$theta = self::posAngleRads($theta - self::AP7_ROT_RADS);
		}

		// perform gnomonic scaling of r
		$r = tan($r);

		// scale for current resolution length u
		$r /= self::RES0_U_GNOMONIC;
		for ($i = 0; $i < $res; $i++) {
			$r *= self::SQRT7;
		}

		return [$face, self::hex2dToIjk($r * cos($theta), $r * sin($theta))];
	}

	/**
	 * Determine the center point in spherical coordinates of a cell given by FaceIJK address at specified resolution.
	 *
	 * @param array{int, int, int} $ijk
	 * @return array{float, float} [lat, lon] in radians
	 */
	private static function faceIjkToGeo(int $face, array $ijk, int $res): array
	{
		[$x, $y] = self::ijkToHex2d($ijk);

		$r = sqrt($x * $x + $y * $y);
		if ($r < self::EPSILON) {
			return self::FACE_CENTER_GEO[$face];
		}

		$theta = atan2($y, $x);

		// scale for current resolution length u
		for ($i = 0; $i < $res; $i++) {
			$r /= self::SQRT7;
		}

		$r *= self::RES0_U_GNOMONIC;

		// perform inverse gnomonic scaling of r
		$r = atan($r);

		// adjust theta for Class III
		if (self::isResolutionClassIII($res)) {
			$theta = self::posAngleRads($theta + self::AP7_ROT_RADS);
		}

		// find theta as an azimuth
		$theta = self::posAngleRads(self::FACE_AXES_AZ_RADS_CII[$face] - $theta);

		// now find the point at (r, theta) from the face center
		[$faceLat, $faceLon] = self::FACE_CENTER_GEO[$face];
		return self::geoAzDistanceRads($faceLat, $faceLon, $theta, $r);
	}

	/**
	 * Adjust FaceIJK address in place so that the resulting cell address is relative to the correct icosahedral face.
	 * Substrate grids are not supported, because they are needed only for cell boundaries.
	 *
	 * @param array{int, int, int} $ijk
	 * @return bool true if there was overage (cell lies on adjacent face), false otherwise
	 */
	private static function adjustOverageClassII(int &$face, array &$ijk, int $res, bool $pentLeading4): bool
	{
		$maxDim = self::MAX_DIM_BY_CII_RES[$res];
		if ($ijk[0] + $ijk[1] + $ijk[2] <= $maxDim) {
			return false;
		}

		if ($ijk[2] > 0) {
			if ($ijk[1] > 0) { // jk "quadrant"
				$orient = self::FACE_NEIGHBORS[$face][self::JK];
			} else { // ik "quadrant"
				$orient = self::FACE_NEIGHBORS[$face][self::KI];

				// adjust for the pentagonal missing sequence
				if ($pentLeading4) {
					// translate origin to center of pentagon, rotate to adjust for the missing sequence and translate
					// the origin back to the center of the triangle
					$origin = [$maxDim, 0, 0];
					$ijk = self::ijkAdd(self::ijkRotate60cw(self::ijkSub($ijk, $origin)), $origin);
				}
			}
		} else { // ij "quadrant"
			$orient = self::FACE_NEIGHBORS[$face][self::IJ];
		}

		[$face, $translate, $ccwRot60] = $orient;

		// rotate and translate for adjacent face
		for ($i = 0; $i < $ccwRot60; $i++) {
			$ijk = self::ijkRotate60ccw($ijk);
		}
		$ijk = self::ijkNormalize(self::ijkAdd($ijk, self::ijkScale($translate, self::UNIT_SCALE_BY_CII_RES[$res])));
		return true;
	}

	/*
	 * ---------------------------------------------------------------------------------------------------------------
	 * Conversion between index and FaceIJK (h3Index.c)
	 * ---------------------------------------------------------------------------------------------------------------
	 */

	/**
	 * Port of _faceIjkToH3()
	 *
	 * @param array{int, int, int} $ijk
	 */
	private static function faceIjkToIndex(int $face, array $ijk, int $res): ?int
	{
		$h = self::INIT;
		$h |= self::CELL_MODE << self::MODE_OFFSET;
		$h |= $res << self::RESOLUTION_OFFSET;

		// build the index from finest resolution up
		for ($r = $res - 1; $r >= 0; $r--) {
			$lastIjk = $ijk;
			if (self::isResolutionClassIII($r + 1)) {
				$ijk = self::upAp7($ijk);
				$lastCenter = self::downAp7($ijk);
			} else {
				$ijk = self::upAp7r($ijk);
				$lastCenter = self::downAp7r($ijk);
			}

			$diff = self::ijkNormalize(self::ijkSub($lastIjk, $lastCenter));
			$h = self::setIndexDigit($h, $r + 1, self::unitIjkToDigit($diff));
		}

		// $ijk should now hold the IJK of the base cell in the coordinate system of the current face
		if ($ijk[0] > self::MAX_FACE_COORD || $ijk[1] > self::MAX_FACE_COORD || $ijk[2] > self::MAX_FACE_COORD) {
			return null; // out of range input
		}

		[$baseCell, $numRots] = self::FACE_IJK_BASE_CELLS[$face][$ijk[0] * 9 + $ijk[1] * 3 + $ijk[2]];
		$h |= $baseCell << self::BASE_CELL_OFFSET;

		// rotate if necessary to get canonical base cell orientation for this base cell
		if (self::isBaseCellPentagon($baseCell)) {
			// force rotation out of missing k-axes sub-sequence
			if (self::leadingNonZeroDigit($h) === self::DIGIT_K_AXES) {
				// check for a cw/ccw offset face; default is ccw
				$baseCellData = self::BASE_CELL_DATA[$baseCell];
				if ($baseCellData[5] === $face || $baseCellData[6] === $face) {
					$h = self::indexRotate60cw($h);
				} else {
					$h = self::indexRotate60ccw($h);
				}
			}

			for ($i = 0; $i < $numRots; $i++) {
				$h = self::indexRotatePent60ccw($h);
			}
		} else {
			for ($i = 0; $i < $numRots; $i++) {
				$h = self::indexRotate60ccw($h);
			}
		}

		return $h;
	}

	/**
	 * Port of _h3ToFaceIjk()
	 *
	 * @return array{int, array{int, int, int}} [face, ijk]
	 */
	private static function indexToFaceIjk(int $h): array
	{
		$baseCell = self::getIndexBaseCell($h);
		$isPentagon = self::isBaseCellPentagon($baseCell);

		// adjust for the pentagonal missing sequence; all of sub-sequence 5 needs to be adjusted (and some of
		// sub-sequence 4 below)
		if ($isPentagon && self::leadingNonZeroDigit($h) === 5) {
			$h = self::indexRotate60cw($h);
		}

		// start with the "home" face and ijk+ coordinates for the base cell
		[$face, $i, $j, $k] = self::BASE_CELL_DATA[$baseCell];
		$ijk = [$i, $j, $k];
		$res = self::getIndexResolution($h);

		// center base cell hierarchy is entirely on this face
		$possibleOverage = $isPentagon || ($res !== 0 && $ijk !== [0, 0, 0]);

		for ($r = 1; $r <= $res; $r++) {
			$ijk = self::isResolutionClassIII($r) ? self::downAp7($ijk) : self::downAp7r($ijk);
			$ijk = self::neighbor($ijk, self::getIndexDigit($h, $r));
		}

		if ($possibleOverage === false) {
			return [$face, $ijk];
		}

		// if we're here we have the potential for an "overage"; i.e., it is possible that cell lies on an adjacent face
		$origIjk = $ijk;

		// if we're in Class III, drop into the next finer Class II grid
		$adjRes = $res;
		if (self::isResolutionClassIII($res)) {
			$ijk = self::downAp7r($ijk);
			$adjRes++;
		}

		// adjust for overage if needed; a pentagon base cell with a leading 4 digit requires special handling
		$pentLeading4 = $isPentagon && self::leadingNonZeroDigit($h) === 4;
		if (self::adjustOverageClassII($face, $ijk, $adjRes, $pentLeading4)) {
			// if the base cell is a pentagon we have the potential for secondary overages
			if ($isPentagon) {
				while (self::adjustOverageClassII($face, $ijk, $adjRes, false)) {
					// continue
				}
			}

			if ($adjRes !== $res) {
				$ijk = self::upAp7r($ijk);
			}
		} elseif ($adjRes !== $res) {
			$ijk = $origIjk;
		}

		return [$face, $ijk];
	}
}
