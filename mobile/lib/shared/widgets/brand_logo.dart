import 'package:flutter/material.dart';

class BrandLogo extends StatelessWidget {
  const BrandLogo({super.key, this.size = 54});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: Colors.white,
        border: Border.all(
          color: Theme.of(context).colorScheme.secondary,
          width: 2,
        ),
        boxShadow: const [
          BoxShadow(
            color: Color(0x24086B4B),
            blurRadius: 18,
            offset: Offset(0, 6),
          ),
        ],
      ),
      child: ClipOval(
        child: Image.asset('assets/images/gofran-logo.jpeg', fit: BoxFit.cover),
      ),
    );
  }
}
