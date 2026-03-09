package help0316_fla
{
   import flash.display.*;
   import flash.events.*;
   
   [Embed(source="/_assets/assets.swf", symbol="symbol21")]
   public dynamic class page1_9 extends MovieClip
   {
      
      public var nextButton:SimpleButton;
      
      public function page1_9()
      {
         super();
         addFrameScript(0,frame1);
      }
      
      internal function frame1() : *
      {
         nextButton.addEventListener(MouseEvent.CLICK,nextButtonClick);
      }
      
      public function nextButtonClick(param1:MouseEvent) : void
      {
         dispatchEvent(new Event("nextButtonClick"));
      }
   }
}

