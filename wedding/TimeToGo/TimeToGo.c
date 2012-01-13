#include <stdio.h>
#include <stdlib.h>
#include <time.h>

#define BUFFER_LEN 100
#define SECONDS_PER_YEAR 31556926.0

/* TODO: Parameterize end-time (hardcoded to 2007-06-02 13:30) */
time_t get_end_time() {
  struct tm time;
  time.tm_sec = 0;
  time.tm_min = 30;
  time.tm_hour = 13;
  time.tm_mday = 2;
  time.tm_mon = 5;
  time.tm_year = 107;

  return mktime(&time);
}


/**
 * MAIN
 */
int main(int argc, char **argv) {
  time_t end_time = get_end_time();
  time_t current_time = time(0);
  double seconds_to_go;
  double time_to_go;

  char buffer[BUFFER_LEN + 1];

  /* Output HTTP header */
  printf("Content Type: text/html\015\012\015\012");

  /* TODO: Use libtemplate to format the output */
  printf("<html><head><title>Time To Go</title></head>\n");
  printf("<body>\n");
  printf("<p>\n");
  
  strftime(buffer, BUFFER_LEN, "%c", localtime(&end_time));
  printf("Event time: %s<br>\n", buffer);

  strftime(buffer, BUFFER_LEN, "%c", localtime(&current_time));
  printf("Current time: %s<br>\n", buffer);

  seconds_to_go = difftime(end_time, current_time);
  printf("Time to go: %0.0f seconds<br>\n", seconds_to_go);

  time_to_go = seconds_to_go / 60;
  printf("Time to go: %0.0f minutes<br>\n", time_to_go);
  
  time_to_go /= 60;
  printf("Time to go: %0.1f hours<br>\n", time_to_go);
  
  time_to_go /= 24;
  printf("Time to go: %0.0f days<br>\n", time_to_go);
  
  time_to_go /= 7;
  printf("Time to go: %0.1f weeks<br>\n", time_to_go);

  printf("Time to go: %0.1f months<br>\n", seconds_to_go / SECONDS_PER_YEAR * 12);

  printf("Time to go: %0.2f years<br>\n", seconds_to_go / SECONDS_PER_YEAR);

  printf("</p>\n");
  printf("</body>\n");
  
  return 0;
}
