<?php
//classes and objects
class Car{
    //properties
    public $brand;
    private $mileage;
    protected $enginestatus;

    //constructor
    public function __construct($brand,$mileage = 0){
        $this->brand = $brand;
        $this->mileage = $mileage;
        $this->enginestatus = 'false';
    }
    //methods
    public function startEngine(){
        $this->enginestatus = 'true';
        echo "The engine started";
    }
    public function drive($distance){
        if($this->enginestatus){
            $this->mileage += $distance;
            echo "You drove {$distance} km.Total: {$this->mileage} km";
        }else{
            echo "Start engine first";
        }
    }
}

//create object
$myCar = new Car("Toyota");
$myCar->startEngine();
$myCar->drive(50);

?>

<?php
//abstract classes
abstract class Shape{
    protected $color;

    public function __construct($color){
      $this->color = $color;  
    }
    //abstract method must be implemented by child class
    abstract public function calculateArea();
    abstract public function calculatePerimeter();

    //concreate method
    public function getColor(){
       return $this->color; 
    }
}

class Circle extends Shape{
    private $radius;

    public function __construct($color,$radius){
        parent::__construct($color); //inherit from a parent class
        $this->radius = $radius;
    }
  public function calculateArea(){
    return pi() * pow($this->radius,2);
  }  
  public function calculatePerimeter(){
    return 2*pi()*$this->radius;
  } 
}

class Rectangle extends Shape{
    private $width,$height;

    public function __construct($color,$width,$height){
        parent::__construct($color);
        $this->width = $width;
        $this->height = $height;
    }
    public function calculateArea(){
        return $this->width * $this->height;
    }
    public function calculatePerimeter(){
        return 2 * ($this->width + $this->height);
    }
}
?>