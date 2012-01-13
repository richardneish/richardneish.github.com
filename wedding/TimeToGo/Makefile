BASENAME = TimeToGo
EXE = ${BASENAME}.cgi
SRC = ${BASENAME}.c
OBJ = $(SRC:%.c=%.o)

CC = gcc

# Optimization flags
CFLAGS += -Os -Wall -pedantic -fomit-frame-pointer -fexpensive-optimizations -s
LIBS += --static

# cgi-utils flags
CFLAGS += -I ../include
LIBS += -L ../lib -lcgi-util

TARGETS = $(EXE)

all: $(TARGETS)

$(EXE): $(OBJ)
	$(CC) -o $@ $(OBJ) $(LIBS)

clean:
	$(RM) *.o

distclean:
	$(RM) Makefile.depends $(TARGETS) *.o
