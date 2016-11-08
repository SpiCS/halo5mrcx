# halo5mrcx

A very quick-and-dirty exporter of Halo 5 mesh resources from "Cached Tag Format" files, written in PHP.

## Licence

Licence is nominally [Creative Commons Attribution/Share-Alike 4.0](https://creativecommons.org/licenses/by-sa/4.0/) in the interests of having research open rather than closed, but I am open to waiving that requirement where it can interface with other existing tools - give me a poke on this account and we can talk.

## Known issues

  * It only touches the mesh resources, and therefore doesn't know the bounding box (which is probably stored in another file).
This means that it'll be squished down into a unit cube (in Blender units), as all the data is stored as a 4-byte integer for each coordinate.  
  
    My presumption is that a 0x0000 value is 0.0 and 0xFFFF is 1.0, and that worked okay besides shoving everything into the same unit cube. It's currently an exercise for the reader to find in which file/location that bounding box is stored and then work the appropriate additives and multipliers in.

  * I've also no idea whether I've got the tangents and normals around the wrong way. Certainly X/Y/Z works, and I presumed the part of each entry with four bytes of alignment blocked off were the tangents - but I could be totally wrong there, and missing something.

  * There could also be transparency that I am missing - this might be why some vertex entries are 28 bytes as opposed to 24.
