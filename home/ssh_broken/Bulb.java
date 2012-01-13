/******************************************************************************
 * 
 * Bulb 
 * -- 
 *
 * This class is just for fun :-)
 *
 * This file is part of "The Java Ssh Applet".
 *
 */
 
import java.awt.Canvas;
import java.awt.Graphics;
import java.awt.Image;
import java.awt.Dimension;


class Bulb extends Canvas {

	private Image imConnected = null, imDisconnected = null;
	boolean state = false;
	Dimension d;


	public Bulb(Image im1, Image im2) {	
		imConnected = im1;
		imDisconnected = im2;
		if (imConnected!=null) 
			d = new Dimension(imConnected.getWidth(this),imConnected.getHeight(this));
		else if (imDisconnected!=null) 
			new Dimension(imDisconnected.getWidth(this),imConnected.getHeight(this));
	}

    public void paint(Graphics  g) {
	
		if (state)  if (imConnected!=null)	g.drawImage(imConnected , 0, 0, this);
		if (!state)  if (imDisconnected!=null) g.drawImage(imDisconnected , 0, 0, this);
	}
    
	public Dimension preferredSize() {	return d;}
    
	public Dimension minimumSize()  {	return d;}
	
	public boolean	imageUpdate(Image  img, int  infoflags, int  x, int  y, int  width, int  height) {
		d = new Dimension(width, height);
		repaint();
		return true;
	};

	public void connected(boolean newState) {
		state = newState;
		repaint();
	}


}