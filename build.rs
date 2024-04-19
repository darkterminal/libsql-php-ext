extern crate cbindgen;

use std::env;
use cbindgen::Language;

fn main() {
    let crate_dir = env::var("CARGO_MANIFEST_DIR").unwrap();

    cbindgen::Builder::new()
      .with_crate(crate_dir)
      .with_language(Language::C)
      .with_no_includes()
      .with_header("#define FFI_LIB \"libsql_php_client.so\"")
      .generate()
      .expect("Unable to generate bindings")
      .write_to_file("target/debug/libsql_php_client.h");
}