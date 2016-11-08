# halo5mrcx

A very quick-and-dirty exporter of Halo 5 mesh resources from "Cached Tag Format" files, written in PHP and intended to be used on the command line.

## Licence

Licence is nominally [Creative Commons Attribution/Share-Alike 4.0](https://creativecommons.org/licenses/by-sa/4.0/) in the interests of having research open rather than closed, but I am open to waiving that requirement where it can interface with other existing tools - give me a poke on this account and we can talk.

If you need a real-name credit, ask me; otherwise just "SpiCS" or "Spi Cybershark" (my online moniker) will do.

## Credits

...are located in the file itself. Certainly particularly indebted to [AnsarOnline/ausardocs](http://github.com/AnvilOnline/AusarDocs), essentially a Halo 5 Forge documentation project, for their work on documenting the Cached Tag Format archive file.

## What I worked out (so far)

  * Where there are X models in the mesh resource, there will be 3 data blocks followed by 2X resource blocks.
  
      * I never worked out what the data blocks were for, but some must have relevance as their size is a multiple of the number of models:
          * Data Block 1 would be an overall header as it's a fixed 56 (0x38) bytes;
          * Data Block 2 and 3... I can't remember off top of my head (writing this away from my testing workstation) but one is 80 (0x50) per model and the other 72 (0x48) bytes per model.
          
      * Then come the vertex blocks, one per model.
          * Each vertex is either 24 (0x18) or 28 (0x1C) bytes. I haven't worked out what the extra four bytes are, but are at offset 0x14 of each row where they exist.
          * Each vertex is padded with four bytes: "0000 00FF". I use this to determine whether a vertex is 24 or 28 bytes in a file.
          * Appears to be stored as UInt16s, with [X, Y, Z] at offset 0x00. Then an aligning byte, then six more UInt16s. I currently have 0x08 as the start of the normals and 0x10 for the tangents [U, V] but I'm probably wrong there.
          
      * Then finally the face blocks, one per model, in the same order.
          * These are simply triangles with three vertex references each - but if there are more than 65535 vertices they are stored as UInt32s, otherwise as UInt16s.
          * They are zero-indexed, but since .OBJ files are 1-indexed for vertices, I offset them by 1.

## Known issues

  * It only touches the mesh resources, and therefore doesn't know the bounding box (which is probably stored in another file).
This means that it'll be squished down into a unit cube (in Blender units), as all the data is stored as a 4-byte integer for each coordinate.  
  
    My presumption is that a 0x0000 value is 0.0 and 0xFFFF is 1.0, and that worked okay besides shoving everything into the same unit cube. It's currently an exercise for the reader to find in which file/location that bounding box is stored and then work the appropriate additives and multipliers in.

  * I've also no idea whether I've got the tangents and normals around the wrong way. Certainly X/Y/Z works, and I presumed the part of each entry with four bytes of alignment blocked off were the tangents - but I could be totally wrong there, and missing something.

  * There could also be transparency that I am missing - this might be why some vertex entries are 28 bytes as opposed to 24.

  * For some reason I wrote the "extract all" thing as a recursive invocation of PHP rather than as a function (what the?) - I will probably clean it up at some stage.
