import cgi

def main():
  print "Content-type: text/html\n\n" # Do this first
  try:
    import worker  # module that does the real work
  except:
    print "<!-- --><hr><h1>Oops.  An error occurred.</h1>"
    cgi.print_exception() # Prints traceback, safely

main()