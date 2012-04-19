#!/usr/bin/python
# Parallel xargs.
# Adam Sampson <ats@offog.org>

import sys, os, signal, getopt

def usage():
	print """parargs by Adam Sampson
Usage: parargs [OPTION] COMMAND [ARGS ...]

For each word read from standard input, run COMMAND with ARGS and the
word as arguments.

-n PROCESSES      Run PROCESSES commands in parallel (default 3)
-v, --verbose     Print progress reports to stderr
-h, --help        Show usage

Report bugs to <ats@offog.org>."""

def main(args):
	try:
		opts, args = getopt.getopt(args, "n:vh", ["verbose", "help"])
	except getopt.GetoptError:
		usage()
		sys.exit(1)

	processes = 3
	verbose = False
	for (o, a) in opts:
		if o in ("-h", "--help"):
			usage()
			sys.exit(0)
		elif o in ("-n"):
			try:
				processes = int(a)
			except ValueError:
				processes = -1
		elif o in ("-v", "--verbose"):
			verbose = True

	if args == [] or processes < 1:
		usage()
		sys.exit(1)

	def get_arg():
		buf = []
		while 1:
			while buf == []:
				l = sys.stdin.readline()
				if l == "":
					yield None
					return
				buf = l.split()
			arg, buf = buf[0], buf[1:]
			yield arg

	reader = get_arg()
	at_eof = False
	running = {}
	while len(running) > 0 or not at_eof:
		while len(running) < processes and not at_eof:
			arg = reader.next()
			if arg is None:
				at_eof = True
				break

			cmd = args + [arg]
			if verbose:
				print >>sys.stderr, "Starting: " + " ".join(cmd)
			pid = os.fork()
			if pid == 0:
				os.execvp(cmd[0], cmd)
				print >>sys.stderr, "Failed to exec:", cmd
				sys.exit(20)
			running[pid] = cmd

		if len(running) > 0:
			(pid, status) = os.wait()
			if verbose:
				print >>sys.stderr, "Finished: " + " ".join(running[pid])
			del running[pid]

if __name__ == "__main__":
	main(sys.argv[1:])

