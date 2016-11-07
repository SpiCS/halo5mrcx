<?php
/**
 * Halo 5 mesh resource custom tag format exporter (mrcx)
 * (c) sp1cs.net 2016-11-01
 * Some rights reserved
 * Licence: Creative Commons BY-SA 4.0
 *
 * Exports mesh resources from a Cached Tag Format file.
 *
 * Currently a little quick+dirty, but the usage is:
 * - php mrcx.php FILENAME [RESOURCE-NUM]
 *
 * Without RESOURCE-NUM, it will will export all it can find into
 * a subdirectory called "(basename FILENAME)-exported" in the
 * same directory where FILENAME exists.
 *
 * It can accept another argument which will export one item only
 * (at the moment "export all" recursively calls itself using this to
 * export as many as it thinks is there).
 *
 * It can't automatically pick through individual levels of
 * detail - that's an exercise for the user. Many files will have
 * four, five, six levels of detail - just look for the things
 * that look similar and are larger in size.
 *
 * Still needs to be tested on bigger things, but it's worked
 * reasonably well for files of low-medium complexity (such as a
 * Magnum gun with 64 layers).
 *
 * With credits:
 * - https://github.com/AnvilOnline/AusarDocs
 *   For their work on documenting the CTF file format
 * - Chernobyl Weyland for giving me the project in the first place :)
 */

// Notes:
// We are LITTLE ENDIAN. For packing:
// C = char (uint8), v = uint16, V = uint32, P = uint64, f = float, d = double
// Vertex rows seem to be either 24 or 28 bytes - there are two extra uint16s [starting at bytes 0x14]
// that I can't work out what they're there for. I'm testing for a magic number at the moment
// of 0xFF00 in the 12th uint, which I haven't come across on a 28-byte row before.
//
// Chernobyl says the dimension boxes should be Xmax=4.5, Ymax=0.3, Zmax=1.0.
// These can be changed up here.
$x_max = 4.5;
$y_max = 0.3;
$z_max = 1.0;

// Main header is length 0x50
$filename = $_SERVER['argv'][1];
$section  = 0;
if (array_key_exists(2, $_SERVER['argv']) === TRUE) {
    $section = (int) $_SERVER['argv'][2];
}

$contents = file_get_contents($filename);
$first_header = substr($contents, 0, 0x50);
$bits = Array(
    'Vmagic',
    'Vdep',
    'Punk08',
    'Pchecksum',
    'Vunk18',
    'VdepCnt',
    'VblkCnt',
    'VtagCnt',
    'VdrefCnt',
    'VtrefCnt',
    'VstrCnt',
    'Vstrsize',
    'VzoneSize',
    'Vhdrsize',
    'Vdatasize',
    'Vressize',
    'chdralign',
    'ctagalign',
    'cresalign',
);
$headers = unpack(implode('/', $bits), $first_header);
$offset = 0x50;

if ($section === 0) {
    // Being naughty and recursively calling ourselves with the correct object number
    // where the object number starts with #1 being the first data reference (usually
    // the fourth block from my small file testing).
    echo 'Extracting '.($headers['drefCnt'] / 2).' objects';
    for ($i = 1; $i <= ($headers['drefCnt'] / 2); $i++) {
        if ($i % 72 === 1) {
            echo "\n";
        }

        echo ".";
        exec('"C:\\Program Files\\PHP\\PHP.exe" "'.__FILE__.'" "'.$_SERVER['argv'][1].'" '.$i,
            $output
        );

        if (count($output) > 0) {
            echo implode("\n", $output);
        }
    }
    echo "\n".'done'."\n";
    exit();
}

$data_blocks = Array();
$file_data = '';

for ($i = 0; $i < $headers['blkCnt']; $i++) {
    // Data block header is 0x10
    $block    = substr($contents, $offset, 0x10);
    $offset  += 0x10;
    $bits = Array(
        'Vsize',
        'vtype',
        'vsection',
        'Poffset',
    );
    $db = $data_blocks[] = unpack(implode('/', $bits), $block);
    // print_r($db);

    if ($db['section'] === 2) {
        $section_num = count($data_blocks);
        $start = $db['offset']+$headers['hdrsize']+$headers['datasize'];
        $data = substr($contents, $start, $db['size']);
        //file_put_contents(dirname($filename).'/'.basename($filename).'.blktype'.$db['type'].'.'.count($data_blocks), $data);

        $paint_row = Array();

        if ($section_num === ($section + $headers['tagCnt'])) {
            $row_lengths = Array(24, 28);

            foreach ($row_lengths as $row_length) {
                $good = TRUE;
                for ($j = 0; $j < strlen($data); $j += $row_length) {
                    $sub_data = substr($data, $j, $row_length);
                    $points    = unpack('v*', $sub_data);
                    if ((int)$points[$row_length / 2] !== 0xFF00) {
                        $good = FALSE;
                        break;
                    }

                    foreach ($points as &$point) {
                        $point /= 65535;


                    $file_data .= '# Vertex #: '.(int)(($j / 24)+1)."\n";
                    $file_data .= 'v '..' '.$points['2'].' '.$points['3']." 1.0\n";
                    $file_data .= 'vn '.$points['5'].' '.$points['6'].' '.$points['7'].' '.$points['8']."\n";
                    $file_data .= 'vt '.$points['9'].' '.$points['10']."\n";
                }

                if ($good === TRUE) {
                    $paint_row['id'] = $section_num - $headers['tagCnt'];
                    $paint_row['vertices'] = ($db['size'] / $row_length);
                    $paint_row['vertex_lemgth'] = $row_length;
                    break;
                }
            }
        }

        if ((int)$section_num === (int)($section + $headers['tagCnt'] + 0.5*$headers['drefCnt'])) {            
            if ($paint_row['vertices'] >= 63356) {
                // Four bytes per face.
                $unpack_cmd = 'V*';
                $row_length = 12;
            } else {
                // Two bytes per face.
                $unpack_cmd = 'v*';
                $row_length = 6;
            }
            $paint_row['triangles'] = ($db['size'] / $row_length);

            $verts = unpack($unpack_cmd, substr($contents, $start, $db['size']));
            $paint_row['vertex_length'] = (max($verts) + 1);
            for ($j = 0; $j < strlen($data); $j += $row_length) {
                $sub_data = substr($data, $j, $row_length);
                $points   = unpack($unpack_cmd, $sub_data);
                foreach ($points as &$point) {
                    // Offset the vertices by one.
                    $point += 1;
                }
                $file_data .= 'f '.implode(' ', $points)."\n";
            }
        }

    }
}

$target_dir = dirname(__FILE__).'/export';
mkdir($target_dir);
file_put_contents($target_dir.'/'.$section.'.obj', $file_data);
