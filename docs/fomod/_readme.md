# FOMOD images

This folder contains the images used in the FOMOD installer. The main file this is a Photoshop
PSD file that is used to export all the images using slices. To save space, it is zipped into
the file [_step-images.psd.zip](./_step-images.psd.zip).

There are images for each ship type and size, as well as for the multipliers. For example,
for the L-Sized mining ships:

- [l-miner.jpg](./l-miner.jpg) - Default image when not using a multiplier
- [l-miner-x2.jpg](./l-miner-x2.jpg) - x2
- [l-miner-x4.jpg](./l-miner-x4.jpg) - x4
- [l-miner-x6.jpg](./l-miner-x6.jpg) - x6
- [l-miner-x8.jpg](./l-miner-x8.jpg) - x8
- [l-miner-x10.jpg](./l-miner-x10.jpg) - x10

## Using custom modifiers

If you set up different modifiers in the mod's configuration and want the FOMOD installer
to work with them, you must add the images for those modifiers. Use the PSD file to create
them and export them to this folder. They will be detected automatically.

Example with a custom modifier of `1.5`:

- [l-miner-x1.5.jpg](./l-miner-x1.5.jpg) 
